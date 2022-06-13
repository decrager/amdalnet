<?php

namespace App\Http\Controllers;

use App\Entity\Project;
use App\Entity\PublicConsultation;
use App\Entity\PublicConsultationDoc;
use App\Http\Resources\PublicConsultationResource;
use App\Utils\Html;
use App\Utils\TemplateProcessor;
use DateInterval;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PDF;

class PublicConsultationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->dokumen) {
            return $this->dokumen($request->idProject);
        }

        if($request->idProject) {
            return PublicConsultation::where('project_id', $request->idProject)->first();
        }

        return PublicConsultationResource::collection(PublicConsultation::all());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'announcement_id' => 'required',
                'project_id' => 'required',
                'event_date' => 'required',
                'participant' => 'required',
                'location' => 'required',
                'address' => 'required',
                'positive_feedback_summary' => 'required',
                'negative_feedback_summary' => 'required',
                'doc_files' => 'required',
                'doc_metadatas' => 'required',
                'doc_photo_metadatas' => 'required',
                'doc_berita_acara_pelaksanaan' => 'required',
                'doc_berita_acara_penunjukan_wakil_masyarakat' => 'required',
                'doc_pengumuman' => 'required',
                'doc_undangan' => 'required',
            ]
        );
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 403);
        } else {
            $validated = $request->all();
            try {
                $datetime = DateTime::createFromFormat('Y-m-d\TH:i:s+', $validated['event_date']);
                $datetime->add(new DateInterval('PT7H'));
                $event_date = $datetime->format('Y-m-d\TH:i:s');
                $validated['event_date'] = $event_date;
            } catch (Exception $e){
                return response()->json(['errors' => 'Invalid event_data'], 400);
            }
            DB::beginTransaction();
            if($request->data_type == 'new') {
                $parent = PublicConsultation::create([
                    'announcement_id' => $validated['announcement_id'],
                    'project_id' => $validated['project_id'],
                    'event_date' => $validated['event_date'],
                    'participant' => $validated['participant'],
                    'location' => $validated['location'],
                    'address' => $validated['address'],
                    'positive_feedback_summary' => $validated['positive_feedback_summary'],
                    'negative_feedback_summary' => $validated['negative_feedback_summary'],
                ]);
            } else {
                $parent = PublicConsultation::findOrFail($request->all()['id']);
                $parent->announcement_id = $validated['announcement_id'];
                $parent->event_date = $validated['event_date'];
                $parent->participant = $validated['participant'];
                $parent->location = $validated['location'];
                $parent->address = $validated['address'];
                $parent->positive_feedback_summary = $validated['positive_feedback_summary'];
                $parent->negative_feedback_summary = $validated['negative_feedback_summary'];
                $parent->save();
            }

            $metadatas = null;
            $doc_files = null;
            try {
                //upload docs
                $photo_metadatas = json_decode($validated['doc_photo_metadatas'], true);
                $metadatas = json_decode($validated['doc_metadatas']);
                $doc_files = json_decode($validated['doc_files']);
            } catch (Exception $e){
                DB::rollBack();
                return response()->json(['errors' => 'Invalid doc metadata'], 400);
            }
            if (count($metadatas) != count($doc_files)){
                DB::rollBack();
                return response()->json(['errors' => 'Metadata mismatch'], 400);
            }
            $doc_inserted = 0;

            // === FOTO DOKUMENTASI === //
            for ($i = 0; $i < count($photo_metadatas); $i++){
                //upload file
                $metadata = $photo_metadatas[$i];
                $file_extension = '';
                $filepath = '';
                try {
                    if($request->hasFile('file-' . $i)) {
                        $file = $request->file('file-' . $i);
                        $file_extension = $file->extension();
                        $image_name = 'docs/pubcons/img/' . uniqid() . '.' . $file_extension;
                        $filepath = Storage::url($image_name);
                        $file->storePubliclyAs('public', $image_name);
                    }
                } catch (Exception $e){
                    DB::rollBack();
                    return response()->json(['errors' => 'Error uploading file'], 500);
                }
                $pubconsDoc = PublicConsultationDoc::create([
                    'public_consultation_id' => $parent->id,
                    'doc_json' => json_encode([
                        'doc_type' => $metadata['doc_type'],
                        'original_filename' => $metadata['filename'],
                        'file_extension' => $file_extension,
                        'filepath' => $filepath,
                        'uploaded_by' => $metadata['uploaded_by'],
                    ]),
                ]);
                if ($pubconsDoc){
                    $doc_inserted++;
                }
            }

            // === LAMPIRAN === //
            for($i = 0; $i < count($metadatas); $i++){
                //upload file
                $metadata = $metadatas[$i];
                $file_extension = '';
                $filepath = '';
                try {
                    // Dokumen Lampiran
                    $file_field_name = sprintf('doc_%s', 
                                        str_replace(' ', '_', strtolower($metadata->doc_type)));
                    $file = $request->file($file_field_name);

                    $filename = uniqid() . '.' . $file->extension();
                    // create subfolder: ba/dh/p/u
                    $exp = explode(' ', $metadata->doc_type);
                    $f = $exp[0][0];
                    $r = count($exp) == 2 ? $exp[1][0] : '';
                    $subfolder = strtolower(sprintf('%s%s', $f, $r));
                    $folder = sprintf('docs/pubcons/%s', $subfolder);
                    $file_name = '/' . $folder . '/' . $filename;
                    // save file
                    $file->storePubliclyAs('public', $file_name);
                    $filepath = Storage::url($file_name);
                    $file_extension = $file->extension();
                } catch (Exception $e){
                    DB::rollBack();
                    return response()->json(['errors' => 'Error uploading file'], 500);
                }
                $pubconsDoc = PublicConsultationDoc::create([
                    'public_consultation_id' => $parent->id,
                    'doc_json' => json_encode([
                        'doc_type' => $metadata->doc_type,
                        'original_filename' => $metadata->filename,
                        'file_extension' => $file_extension,
                        'filepath' => $filepath,
                        'uploaded_by' => $metadata->uploaded_by,
                    ]),
                ]);
                if ($pubconsDoc){
                    $doc_inserted++;
                }
            }
            if ($parent && ($doc_inserted == (count($metadatas) + count($photo_metadatas)))){
                DB::commit();
            } else {
                DB::rollBack();
            }
            $created = PublicConsultation::with('docs')->get()->where('id', '=', $parent->id)->first();
            return new PublicConsultationResource($created);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Entity\PublicConsultation  $publicConsultation
     * @return \Illuminate\Http\Response
     */
    public function show(PublicConsultation $publicConsultation)
    {
        return PublicConsultation::with('docs')->get()->where('id', '=', $publicConsultation->id)->first();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Entity\PublicConsultation  $publicConsultation
     * @return \Illuminate\Http\Response
     */
    public function edit(PublicConsultation $publicConsultation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Entity\PublicConsultation  $publicConsultation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PublicConsultation $publicConsultation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Entity\PublicConsultation  $publicConsultation
     * @return \Illuminate\Http\Response
     */
    public function destroy(PublicConsultation $publicConsultation)
    {
        //
    }

    private function dokumen($id_project)
    {
        $project = Project::findOrFail($id_project);
        $public_consultation = PublicConsultation::where('project_id', $id_project)->first();

        $pdf = PDF::loadView('document.template_hasil_konsultasi_publik', compact('project', 'public_consultation'));
        return $pdf->download('Konsultasi Publik.pdf');

    }
}
