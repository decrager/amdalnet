<?php

namespace App\Http\Controllers;

use App\Entity\DocumentAttachment;
use App\Entity\EnvImpactAnalysis;
use App\Entity\EnvManagePlan;
use App\Entity\EnvMonitorPlan;
use App\Entity\Initiator;
use App\Entity\MeetingReport;
use App\Entity\OssNib;
use App\Entity\Project;
use App\Entity\ProjectSkkl;
use App\Entity\SubProject;
use App\Services\OssService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpWord\TemplateProcessor;

class SKKLController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->information) {
            return $this->getInformation($request->idProject);
        }

        if($request->document) {
            if($request->uklUpl) {
                return $this->getDocumentUklUpl($request->idProject);
            }

            return $this->getDocument($request->idProject);
        }

        if($request->map) {
            return Project::findOrFail($request->idProject);
        }

        if ($request->skkl) {
            return $this->getDetailSkkl($request->idProject);
        }

        if ($request->skklOss) {
            $type = 'skkl';
            if (isset($request->type) && !empty($request->type)) {
                $type = $request->type;
            }
            return $this->getDataChecklistFileUrl($request->idProject, $type);
        }

        if ($request->spplOss || $request->pkplhOss) {
            return $this->getFileFromOSS($request->url);
        }

        if ($request->sppl1){
            // $templateProcessor = storage_path('app/public/template/template_sppl_pem.docx');
            $templateProcessor = new TemplateProcessor(storage_path('app/public/template/template_sppl_pem.docx')) ;
            $tmpName = $templateProcessor->save();
            Storage::disk('public')->put('skkl/' . 'template_sppl_pem.docx', file_get_contents($tmpName));
            unlink($tmpName);
            $spplDownload = Storage::disk('public')->temporaryUrl('skkl/' . 'template_sppl_pem.docx', now()->addMinutes(env('TEMPORARY_URL_TIMEOUT')));
            return [
                'url' => $spplDownload
            ];
        }
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
        $data = $request->all();
        if($request->hasFile('skkl')) {
            $project = Project::findOrFail($data['idProject']);
            $file_name = $project->project_title .' - ' . $project->initiator->name;


             //create file
             $file = $request->file('skkl');
             $name = 'skkl/' . $file_name . '.' . $file->extension();
             $file->storePubliclyAs('public', $name);

             //create environmental expert
            $skkl = ProjectSkkl::where('id_project', $data['idProject'])->first();

            if(!$skkl) {
                $skkl = new ProjectSkkl();
                $skkl->id_project = $data['idProject'];
            }

             $skkl->file = $name;
             $skkl->save();

             // send status 45 to OSS
             OssService::receiveLicenseStatus($project, '45');

              // === WORKFLOW === //
            if($project->marking == 'amdal.recommendation-signed') {
                $project->workflow_apply('publish-amdal-skkl');
                $project->save();
            } else if($project->marking == 'uklupl-mt.recommendation-signed') {
                $project->workflow_apply('publish-uklupl-pkplh');
                $project->save();
            }

             return response()->json(['message' => 'success']);
        }

        return response()->json(['message' => 'failed'], 404);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    private function getInformation($idProject) {
        $data = [];
        $beritaAcara = MeetingReport::where('id_project', $idProject)->first();

        $project = Project::findOrFail($idProject);

        // ========= PROJECT ADDRESS =========== //
        $district = '';
        $province = '';
        $address = '';
        if($project->address && $project->address->first()) {
            $district = $project->address->first()->district;
            $province = $project->address->first()->province;
            $address = $project->address->first()->address;
        }
        $data[] = [
            'title' => 'Nama Kegiatan',
            'description' => $project->project_title,
        ];
        $data[] = [
            'title' => 'Bidang Usaha/Kegiatan',
            'description' => 'Bidang ' . $project->sector
        ];
        $data[] = [
            'title' => 'Skala/Besaran',
            'description' => $project->scale . ' ' . $project->scale_unit
        ];
        $data[] = [
            'title' => 'Alamat',
            'description' => $address
        ];
        $data[] = [
            'title' => 'Pemrakarsa',
            'description' => $project->initiator->name
        ];
        $data[] = [
            'title' => 'Penanggung Jawab',
            'description' => $project->initiator->pic
        ];
        $data[] = [
            'title' => 'Alamat Pemrakarsa',
            'description' => $project->initiator->address
        ];
        $data[] = [
            'title' => 'No Telepon Pemrakarsa',
            'description' => $project->initiator->phone
        ];
        $data[] = [
            'title' => 'Email Pemrakarsa',
            'description' => $project->initiator->email
        ];
        $data[] = [
            'title' => 'Provinsi/Kota',
            'description' => $province . '/' . $district
        ];
        $data[] = [
            'title' => 'Deskripsi Kegiatan',
            'description' => null,
            'wider' => true,
            'type' => 'title'
        ];
        $data[] = [
            'title' => $project->description,
            'description' => null,
            'wider' => true,
            'type' => 'description'
        ];
        $data[] = [
            'title' => 'Deskripsi Lokasi',
            'description' => null,
            'wider' => true,
            'type' => 'title'
        ];
        $data[] = [
            'title' => $project->location_desc,
            'description' => null,
            'wider' => true,
            'type' => 'description'
        ];

        return $data;
    }

    private function getDocument($idProject) {
        $project = Project::findOrFail($idProject);

        // === DOKUMEN KA, ANDAL & RKL RPL === //
        $document_attachment = DocumentAttachment::where('id_project', $idProject)
                                    ->whereIn('type', ['Formulir KA', 'Dokumen Andal', 'Dokumen RKL RPL'])
                                    ->get();
        $dokumen_ka = $document_attachment->where('type', 'Formulir KA')->first() ?
                      $document_attachment->where('type', 'Formulir KA')->first()->attachment : null;
        $dokumen_andal = $document_attachment->where('type', 'Dokumen Andal')->first() ?
                         $document_attachment->where('type', 'Dokumen Andal')->first()->attachment : null;
        $dokumen_rkl_rpl = $document_attachment->where('type', 'Dokumen RKL RPL')->first() ?
                           $document_attachment->where('type', 'Dokumen RKL RPL')->first()->attachment : null;

        // Date
        $andalDate = '';
        $andal = EnvImpactAnalysis::whereHas('impactIdentification', function($q) use($idProject) {
           $q->where('id_project', $idProject);
        })->orderBy('updated_at', 'desc')->first();
        if($andal) {
            $andalDate = $andal->updated_at->locale('id')->isoFormat('D MMMM Y');
        }

        $rklDate = '';
        $rkl = EnvManagePlan::whereHas('impactIdentification', function($q) use($idProject) {
            $q->where('id_project', $idProject);
         })->orderBy('updated_at', 'desc')->first();
         if($rkl) {
             $rklDate = $rkl->updated_at;
         }

        $rplDate = '';
        $rpl = EnvMonitorPlan::whereHas('impactIdentification', function($q) use($idProject) {
            $q->where('id_project', $idProject);
         })->orderBy('updated_at', 'desc')->first();
         if($rpl) {
            $rplDate = $rpl->updated_at;
        }

        $rklRplDate = '';
        if($rklDate == '') {
            $rklRplDate = $rplDate;
        } else if ($rplDate == '') {
            $rklRplDate = $rklDate;
        } else {
            $rklRplDate = $rklDate > $rplDate ? $rklDate->locale('id')->isoFormat('D MMMM Y') : $rplDate->locale('id')->isoFormat('D MMMM Y');
        }

        // === SKKL === //
        $skkl_download_name = null;
        $update_date_skkl = null;
        $skkl = ProjectSkkl::where('id_project', $idProject)->first();
        if($skkl) {
            $skkl_download_name = $skkl->file;
            $update_date_skkl = $skkl->updated_at->locale('id')->isoFormat('D MMMM Y');
        } else {
            // ============== PROJECT ADDRESS =============== //
            $location = '';
            if($project->address) {
                if($project->address->first()) {
                    $district = $project->address->first()->district;
                    $province = $project->address->first()->province;
                    $address = $project->address->first()->address;
                    $location = $address . ' ' . ucwords(strtolower($district)) . ', Provinsi ' . $province;
                }
            }

            // PHPWord
            $templateProcessor = new TemplateProcessor(storage_path('app/public/template/template_skkl.docx'));
            $templateProcessor->setValue('project_title_big', strtoupper($project->project_title));
            $templateProcessor->setValue('pemrakarsa_big', strtoupper($project->initiator->name));
            $templateProcessor->setValue('project_title', $project->project_title);
            $templateProcessor->setValue('pemrakarsa', $project->initiator->name);
            $templateProcessor->setValue('project_type', $project->project_type);
            $templateProcessor->setValue('pic', $project->initiator->name);
            $templateProcessor->setValue('pic_position', $project->initiator->pic_role);
            $templateProcessor->setValue('pemrakarsa_address', $project->initiator->address);
            $templateProcessor->setValue('location', $location);

            $save_file_name = str_replace('/', '-', $project->project_title) .' - ' . $project->initiator->name . '.docx';
            if (!Storage::disk('public')->exists('skkl')) {
                Storage::disk('public')->makeDirectory('skkl');
            }

            // $templateProcessor->saveAs(Storage::disk('public')->path('skkl/' . $save_file_name));
            // $skkl_download_name = Storage::url('skkl/' . $save_file_name);
            $tmpName = $templateProcessor->save();
            Storage::disk('public')->put('skkl/' . $save_file_name, file_get_contents($tmpName));
            unlink($tmpName);

            $skkl_download_name = Storage::disk('public')->temporaryUrl('skkl/' . $save_file_name, now()->addMinutes(env('TEMPORARY_URL_TIMEOUT')));

            $update_date_skkl = $project->updated_at->locale('id')->isoFormat('D MMMM Y');
        }


        return [
                [
                    'name' => 'Persetujuan Lingkungan SKKL',
                    // 'file' => $skkl_download_name,
                    'file' => Storage::disk('public')->temporaryUrl($skkl_download_name, now()->addMinutes(env('TEMPORARY_URL_TIMEOUT'))),
                    'updated_at' => $update_date_skkl,
                    'uploaded' => $skkl
                ],
                [
                    'name' => 'Dokumen KA',
                    // 'file' => $dokumen_ka,
                    'file' => Storage::disk('public')->temporaryUrl($dokumen_ka, now()->addMinutes(env('TEMPORARY_URL_TIMEOUT'))),
                    'updated_at' => $project->updated_at->locale('id')->isoFormat('D MMMM Y')
                ],
                [
                    'name' => 'Dokumen ANDAL',
                    // 'file' =>  $dokumen_andal,
                    'file' => Storage::disk('public')->temporaryUrl($dokumen_andal, now()->addMinutes(env('TEMPORARY_URL_TIMEOUT'))),
                    'updated_at' => $andalDate
                ],
                [
                    'name' => 'Dokumen RKL RPL',
                    // 'file' => $dokumen_rkl_rpl,
                    'file' => Storage::disk('public')->temporaryUrl($dokumen_rkl_rpl, now()->addMinutes(env('TEMPORARY_URL_TIMEOUT'))),
                    'updated_at' => $rklRplDate
                ]
            ];
    }

    private function getDocumentUklUpl($idProject) {
        $project = Project::findOrFail($idProject);

        // ============== PROJECT ADDRESS =============== //
        $location = '';
        if($project->address) {
            if($project->address->first()) {
                $district = $project->address->first()->district;
                $province = $project->address->first()->province;
                $address = $project->address->first()->address;
                $location = $address . ' ' . ucwords(strtolower($district)) . ', Provinsi ' . $province;
            }
        }

        // PHPWord
        $templateProcessor = new TemplateProcessor(storage_path('app/public/template/template_pkplh.docx'));
        $templateProcessor->setValue('project_title_big', strtoupper($project->project_title));
        $templateProcessor->setValue('pemrakarsa_big', strtoupper($project->initiator->name));
        $templateProcessor->setValue('project_title', $project->project_title);
        $templateProcessor->setValue('pemrakarsa', $project->initiator->name);
        $templateProcessor->setValue('pemrakarsa_nib', $project->initiator->nib);
        $templateProcessor->setValue('project_type', $project->project_type);
        $templateProcessor->setValue('pemrakarsa_pic', $project->initiator->name);
        $templateProcessor->setValue('pemrakarsa_position', $project->initiator->pic_role);
        $templateProcessor->setValue('pemrakarsa_address', $project->initiator->address);
        $templateProcessor->setValue('project_address', $location);

        $save_file_name = str_replace('/', '-', $project->project_title) .' - ' . $project->initiator->name . '.docx';
        if (!Storage::disk('public')->exists('pkplh')) {
            Storage::disk('public')->makeDirectory('pkplh');
        }

        // $templateProcessor->saveAs(Storage::disk('public')->path('pkplh/' . $save_file_name));
        $tmpName = $templateProcessor->save();
        Storage::disk('public')->put('pkplh/' . $save_file_name, file_get_contents($tmpName));
        unlink($tmpName);

        $uklDate = '';
        $ukl = EnvManagePlan::whereHas('impactIdentification', function($q) use($idProject) {
            $q->where('id_project', $idProject);
         })->orderBy('updated_at', 'desc')->first();
         if($ukl) {
             $uklDate = $ukl->updated_at;
         }

        $uplDate = '';
        $upl = EnvMonitorPlan::whereHas('impactIdentification', function($q) use($idProject) {
            $q->where('id_project', $idProject);
         })->orderBy('updated_at', 'desc')->first();
         if($upl) {
            $uplDate = $upl->updated_at;
        }

        $uklUplDate = '';
        if($uklDate == '') {
            $uklUplDate = $uplDate;
        } else if ($uplDate == '') {
            $uklUplDate = $uklDate;
        } else {
            $uklUplDate = $uklDate > $uplDate ? $uklDate->locale('id')->isoFormat('D MMMM Y') : $uplDate->locale('id')->isoFormat('D MMMM Y');
        }

        // === WORKFLOW === //
        // if($project->marking == 'uklupl-mt.recommendation-signed') {
        //     $project->workflow_apply('publish-uklupl-pkplh');
        //     $project->save();
        // }


        return [
                [
                    'name' => 'PKPLH',
                    // 'file' => Storage::url('pkplh/' . $save_file_name),
                    'file' => Storage::disk('public')->temporaryUrl('pkplh/' . $save_file_name, now()->addMinutes(env('TEMPORARY_URL_TIMEOUT'))),
                    'updated_at' => $project->updated_at->locale('id')->isoFormat('D MMMM Y')
                ],
                [
                    'name' => 'Dokumen UKL UPL',
                    // 'file' => Storage::url('workspace/ukl-upl-' . strtolower($project->project_title) . '.docx'),
                    'file' => Storage::disk('public')->temporaryUrl('workspace/ukl-upl-' . strtolower($project->project_title) . '.docx', now()->addMinutes(env('TEMPORARY_URL_TIMEOUT'))),
                    'updated_at' => $uklUplDate
                ]
            ];
    }

    private function getDetailSkkl($idProject)
    {
        $skkl = ProjectSkkl::where('id_project', $idProject)->first();

        if (!$skkl) {
            Log::error('SKKL with id_project ' . $idProject . ' not found.');
            return false;
        }

        return $skkl;
    }

    private function getDataNib($idProject, $type)
    {
        $project = Project::findOrFail($idProject);
        $initiator = Initiator::find($project->id_applicant);
        if (!$initiator) {
            Log::error('Initiator not found');
            return false;
        }
        $ossNib = OssNib::where('nib', $initiator->nib)->first();
        if (!$ossNib) {
            Log::error('OSSNib not found');
            return false;
        }
        $jsonContent = $ossNib->json_content;
        $idIzinSkkl = isset($ossNib->id_izin) ? $ossNib->id_izin : null;
        $idIzinList = [];
        $fileIzin = null;
        if ($type == 'sppl' || $type == 'pkplh') {
            $subProject = SubProject::where('id_project', $idProject)
                ->orderBy('created_at', 'desc')
                ->first();
            if ($subProject) {
                foreach ($jsonContent['data_checklist'] as $c) {
                    if ($c['id_proyek'] == $subProject->id_proyek) {
                        if (($type == 'sppl' && str_contains(strtolower($c['nama_izin']), 'sppl'))
                            || ($type == 'pkplh' && str_contains(strtolower($c['nama_izin']), 'pkplh'))) {
                            array_push($idIzinList, $c['id_izin']);
                            if ($c['file_izin'] != '-') {
                                $fileIzin = $c['file_izin'];
                            }
                        }
                    }
                }
                if (count($idIzinList) == 0) {
                    $dataIzin = $this->getDataIzinFromInqueryNIB($jsonContent['nib'], $subProject->id_proyek, $type);
                    if ($dataIzin != null) {
                        array_push($idIzinList, $dataIzin['id_izin']);
                        if ($dataIzin['file_izin'] != '-') {
                            $fileIzin = $dataIzin['file_izin'];
                        }
                    }
                }
            }
        }
        return [
            'nib' => isset($jsonContent['nib']) ? $jsonContent['nib'] : null,
            'id_izin' => $idIzinSkkl,
            'id_izin_list' => $idIzinList,
            'file_izin' => $fileIzin,
        ];
    }

    private function getDataIzinFromInqueryNIB($nib, $id_proyek, $type)
    {
        $resp = OssService::inqueryNIB($nib);
        $respNib = $resp['responinqueryNIB'];
        if ((int)$respNib['kode'] == 200) {
            foreach ($respNib['dataNIB']['data_checklist'] as $c) {
                if ($c['id_proyek'] == $id_proyek) {
                    if (($type == 'sppl' && str_contains(strtolower($c['nama_izin']), 'sppl'))
                        || ($type == 'pkplh' && str_contains(strtolower($c['nama_izin']), 'pkplh'))) {
                        return $c;
                    }
                }
            }
        }
        return null;
    }

    private function getDataChecklistFileUrl($idProject, $type = 'skkl')
    {
        $dataNib = $this->getDataNib($idProject, $type);
        $fileUrl = null;
        if (isset($dataNib['file_izin']) && !empty($dataNib['file_izin'])) {
            $fileUrl = $dataNib['file_izin'];
        }
        // double check : akses inqueryFileDS
        if ($fileUrl == null) {
            if ($type == 'sppl' || $type == 'pkplh') {
                if (isset($dataNib['id_izin_list'])) {
                    foreach ($dataNib['id_izin_list'] as $id_izin) {
                        $resp = OssService::inqueryFileDS($dataNib['nib'], $id_izin);
                        if ((int)$resp['responInqueryFileDS']['status'] == 200 &&
                            isset($resp['responInqueryFileDS']['url_file_ds'])) {
                            $fileUrl = $resp['responInqueryFileDS']['url_file_ds'];
                            break;
                        }
                    }
                }
            } else {
                // SKKL
                try {
                    $resp = OssService::inqueryFileDS($dataNib['nib'], $dataNib['id_izin']);
                    $fileUrl = $resp['responInqueryFileDS']['url_file_ds'];
                } catch (Exception $e) {
                    Log::error($e->getMessage());
                }
            }
        }

        return [
            'file_url' => $fileUrl,
            'user_key' => env('OSS_USER_KEY'),
        ];
    }

    private function getFileFromOSS($url)
    {
        $response = Http::withHeaders([
            'user_key' => env('OSS_USER_KEY'),
        ])->get($url);
        header('Content-Type: application/pdf');
        $headers = $response->getHeaders();
        header('Content-Disposition: ' . $headers['Content-Disposition'][0]);
        return $response->getBody();
    }
}
