<?php

namespace App\Http\Controllers;

use App\Entity\AndalAttachment;
use App\Entity\AndalComment;
use App\Entity\Comment;
use Illuminate\Support\Facades\Log;
use App\Entity\ComponentType;
use App\Entity\DocumentAttachment;
use App\Entity\EnvImpactAnalysis;
use App\Entity\FormulatorTeam;
use App\Entity\FormulatorTeamMember;
use App\Entity\HolisticEvaluation;
use App\Entity\ImpactAnalysisDetail;
use App\Entity\ImpactIdentification;
use App\Entity\ImpactIdentificationClone;
use App\Entity\ImportantTrait;
use App\Entity\KaReview;
use App\Entity\Lpjp;
use App\Entity\Project;
use App\Entity\ProjectKegiatanLainSekitar;
use App\Entity\ProjectStage;
use App\Entity\PublicConsultation;
use App\Entity\RonaAwal;
use App\Entity\SubProject;
use App\Entity\SubProjectComponent;
use App\Entity\SubProjectRonaAwal;
use App\Laravue\Models\User;
use App\Notifications\RklRPlNotification;
use App\Utils\Document;
use App\Utils\Html;
use App\Utils\ListRender;
use App\Utils\TemplateProcessor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\Element\Table;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PDF;

class AndalComposingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->documentReview) {
            return $this->documentReview($request->idProject);
        }

        if($request->checkDocument) {
            if(!Storage::disk('public')->exists('workspace')) {
                return 'false';
            }

            $document_attachment = DocumentAttachment::where([['id_project', $request->idProject],['type', 'Dokumen Andal']])->first();
            if($document_attachment) {
                return 'true';
            }

            return 'false';
        }

        if($request->projectName) {
            $project = Project::findOrFail($request->idProject);
            return $project->project_title;
        }

        if($request->checkWorkspace) {
            return $this->checkWorkspace($request->idProject);
        }

        if($request->attachment) {
            return $this->getAttachment($request->idProject);
        }

        if($request->holisticEvaluation) {
            $evaluation = HolisticEvaluation::where('id_project', $request->idProject)->count();
            if($evaluation > 0) {
                $evaluation = HolisticEvaluation::where('id_project', $request->idProject)->first();
                return $evaluation->description;
            }

            return null;
        }

        if ($request->pdf) {
            return $this->exportKAPDF($request->idProject, $request->type);
        }

        if ($request->formulir) {
            return $this->formulirKa($request->idProject, $request->type);
        }

        if ($request->docs) {
            return $this->dokumen($request->idProject);
        }

        if ($request->comment) {
            $comments = [];
            if ($request->role == 'true') {
                $comments = AndalComment::where([['id_project', $request->idProject], ['id_user', $request->idUser]])->orderBy('id', 'DESC')->with(['user' => function ($q) {
                    $q->select(['id', 'name']);
                }])->get();
            } else {
                $comments = AndalComment::where('id_project', $request->idProject)->orderBy('id', 'DESC')->with(['user' => function ($q) {
                    $q->select(['id', 'name']);
                }])->get();
            }

            return $comments;
        }

        if ($request->project) {
            return Project::whereHas('impactIdentifications')->get();
        }

        if ($request->lastTime && $request->idProject) {
            $id_project = $request->idProject;
            $time = EnvImpactAnalysis::whereHas('impactIdentification', function ($q) use ($id_project) {
                $q->where('id_project', $id_project);
            })->orderBy('updated_at', 'DESC')->first();

            if ($time) {
                return 'Diperbarui ' . $time->updated_at->locale('id')->diffForHumans();
            } else {
                return null;
            }
        }

        if ($request->idProject) {
            $ids = [4, 1, 2, 3];
            $stages = ProjectStage::select('id', 'name')->get()->sortBy(function ($model) use ($ids) {
                return array_search($model->getKey(), $ids);
            });

            $project = Project::where('id', $request->idProject)->whereHas('impactIdentificationsClone', function ($query) {
                $query->whereHas('envImpactAnalysis');
            })->first();

            if ($project) {
                return $this->getEnvImpactAnalysis($request->idProject, $stages);
            } else {
                return $this->getImpactNotifications($request->idProject, $stages);
            }
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if($request->type == 'attachment') {
            $project = Project::findOrFail($request->idProject);

            if($request->ktr) {
                $ktrName = '';
                $fileKtr = $this->base64ToFile($request->ktr);
                $ktrName = 'project/ktr/' . uniqid() . '.' . $fileKtr['extension'];
                Storage::disk('public')->put($ktrName, $fileKtr['file']);
                $project->ktr = $ktrName;
            }

            if($request->pA) {
                $pAName = '';
                    $filePa = $this->base64ToFile($request->pA);
                    $pAName = 'project/preAgreement/' . uniqid() . '.' . $filePa['extension'];
                    Storage::disk('public')->put($pAName, $filePa['file']);
                    $project->pre_agreement_file = $pAName;
            }

            if($request->pippib) {
                $pippibName = '';
                    $filePippib = $this->base64ToFile($request->pippib);
                    $pippibName = 'project/pippib/' . uniqid() . '.' . $filePippib['extension'];
                    Storage::disk('public')->put($pippibName, $filePippib['file']);
                    $project->ppib_file = $pippibName;
            }

            if($request->kL) {
                $kLName = '';
                    $fileKl = $this->base64ToFile($request->kL);
                    $kLName = 'project/kawasanlindung/' . uniqid() . '.' . $fileKl['extension'];
                    Storage::disk('public')->put($kLName, $fileKl['file']);
                    $project->kawasan_lindung_file = $kLName;
            }

            $project->save();

            // === ANDAL ATTACHMENT === //
            // === PERTEK === //
            $pertek = json_decode($request->pertek, true);
            if(count($pertek) > 0) {
                for($i = 0; $i < count($pertek); $i++) {
                    $fileRequest = 'file-pertek-' . $i;

                    if($request->input($fileRequest)) {
                        $attachment = new AndalAttachment();
                        $attachment->id_project = $request->idProject;
                        $attachment->name = $pertek[$i];

                        $file = $this->base64ToFile($request->input($fileRequest));
                        $fileName = 'project/andal-attachment/' . uniqid() . '.' . $file['extension'];
                        Storage::disk('public')->put($fileName, $file['file']);
                        $attachment->file = $fileName;
                        $attachment->is_pertek = true;

                        $attachment->save();
                    }
                }
            }

            // ==== LAINNYA === //
            $others = json_decode($request->others, true);

            if(count($others) > 0) {
                for($i = 0; $i < count($others); $i++) {
                    $fileRequest = 'file-' . $i;

                    if($request->input($fileRequest)) {
                        $attachment = new AndalAttachment();
                        $attachment->id_project = $request->idProject;
                        $attachment->name = $others[$i];

                        $file = $this->base64ToFile($request->input($fileRequest));
                        $fileName = 'project/andal-attachment/' . uniqid() . '.' . $file['extension'];
                        Storage::disk('public')->put($fileName, $file['file']);
                        $attachment->file = $fileName;

                        $attachment->save();
                    }
                }
            }

            $deleted = json_decode($request->deleted, true);

            if(count($deleted) > 0) {
                $files = AndalAttachment::whereIn('id', $deleted)->get();
                foreach($files as $file) {
                    Storage::disk('public')->delete($file->rawFile());
                }

                AndalAttachment::whereIn('id', $deleted)->delete();
            }

            return response()->json(['message' => 'success']);
        }

        if($request->type == 'holisticEvaluation') {
            $evaluation = HolisticEvaluation::where('id_project', $request->idProject)->count();
            if($evaluation > 0) {
                $evaluation = HolisticEvaluation::where('id_project', $request->idProject)->first();
                $evaluation->description = $request->description;
                $evaluation->save();
            } else {
                $evaluation = new HolisticEvaluation();
                $evaluation->id_project = $request->idProject;
                $evaluation->description = $request->description;
                $evaluation->save();
            }

            return response()->json(['message' => 'success']);
        }

        if ($request->type == 'formulir') {
            $project = Project::findOrFail($request->idProject);
            if(Storage::disk('public')->exists('formulir/ka-andal/' . strtolower($project->project_title) . '.docx')) {
                Storage::disk('public')->delete('formulir/ka-andal/' . strtolower($project->project_title) . '.docx');
            }

            if ($request->hasFile('docx')) {
                //create file
                $file = $request->file('docx');
                $name = '/formulir/ka-andal-' . strtolower($project->project_title) . '.docx';
                $file->storePubliclyAs('public', $name);

                return response()->json(['message' => 'success']);
            }

            return;
        }

        if ($request->type == 'impact-comment') {
            $data = $request->all();

            Validator::make($data, [
                'description' => 'required',
                'column_type' => 'required'
            ], [
                'description.required' => 'Komentar wajib diisi',
                'column_type.required' => 'Kolom wajib dipilih'
            ])->validate();

            $comment = new Comment();
            $comment->id_user = $request->id_user;
            $comment->id_impact_identification = $request->id_impact_identification;
            $comment->description = $request->description;
            $comment->column_type = $request->column_type;
            $comment->document_type = 'andal';
            $comment->is_checked = false;
            $comment->save();

            return [
                'id' => $comment->id,
                'id_impact_identification' => $comment->id_impact_identification,
                'created_at' => $comment->updated_at->locale('id')->isoFormat('D MMMM Y hh:mm:ss'),
                'user' => $comment->user->name,
                'is_checked' => $comment->is_checked,
                'description' => $comment->description,
                'column_type' => $comment->column_type,
                'replies' => []
            ];
        }

        if ($request->type == 'impact-comment-reply') {
            $data = $request->all();

            Validator::make($data, [
                'description' => 'required',
            ], [
                'description.required' => 'Balasan wajib diisi',
            ])->validate();

            $comment = new Comment();
            $comment->id_user = $request->id_user;
            $comment->id_impact_identification = $request->id_impact_identification;
            $comment->description = $request->description;
            $comment->document_type = 'andal';
            $comment->reply_to = $request->id_comment;
            $comment->save();

            return [
                'id' => $comment->id,
                'created_at' => $comment->updated_at->locale('id')->isoFormat('D MMMM Y hh:mm:ss'),
                'description' => $comment->description
            ];
        }

        if ($request->type == 'checked-comment') {
            $comment = Comment::findOrFail($request->id);
            if ($comment->is_checked) {
                $comment->is_checked = false;
            } else {
                $comment->is_checked = true;
            }
            $comment->save();

            return $comment->is_checked;
        }

        if ($request->type == 'comment') {
            $comment = new AndalComment();
            $comment->id_project = $request->idProject;
            $comment->id_user = $request->idUser;
            $comment->comment = $request->comment;
            $comment->save();

            return AndalComment::whereKey($comment->id)->with('user')->first();
        }

        $analysis = $request->analysis;
        $ids = [];

        for ($i = 0; $i < count($analysis); $i++) {
            $envAnalysis = EnvImpactAnalysis::where('id_impact_identifications', $analysis[$i]['id'])->first();
            if($envAnalysis) {
                $envAnalysis->impact_eval_result = $analysis[$i]['impact_eval_result'];
                $envAnalysis->impact_type = $analysis[$i]['impact_type'];
                $envAnalysis->studies_condition = $analysis[$i]['studies_condition'];
                $envAnalysis->condition_dev_no_plan = $analysis[$i]['condition_dev_no_plan'];
                $envAnalysis->condition_dev_with_plan = $analysis[$i]['condition_dev_with_plan'];
                $envAnalysis->impact_size_difference = $analysis[$i]['impact_size_difference'];
                $envAnalysis->save();

                $important_trait = $analysis[$i]['important_trait'];

                for ($a = 0; $a < count($important_trait); $a++) {
                    $detail = ImpactAnalysisDetail::where([['id_env_impact_analysis', $envAnalysis->id], ['id_important_trait', $important_trait[$a]['id']]])->first();
                    if($detail) {
                        $detail->important_trait = $important_trait[$a]['important_trait'];
                        $detail->description = $important_trait[$a]['desc'];
                        $detail->save();
                    } else {
                        $detail = new ImpactAnalysisDetail();
                        $detail->id_env_impact_analysis = $envAnalysis->id;
                        $detail->id_important_trait = $important_trait[$a]['id'];
                        $detail->important_trait = $important_trait[$a]['important_trait'];
                        $detail->description = $important_trait[$a]['desc'];
                        $detail->save();
                    }
                }

                $ids[] = $analysis[$i]['id'];
            } else {
                for ($i = 0; $i < count($analysis); $i++) {
                    $envAnalysis = new EnvImpactAnalysis();
                    $envAnalysis->id_impact_identifications = $analysis[$i]['id'];
                    $envAnalysis->impact_eval_result = $analysis[$i]['impact_eval_result'];
                    $envAnalysis->impact_type = $analysis[$i]['impact_type'];
                    $envAnalysis->studies_condition = $analysis[$i]['studies_condition'];
                    $envAnalysis->condition_dev_no_plan = $analysis[$i]['condition_dev_no_plan'];
                    $envAnalysis->condition_dev_with_plan = $analysis[$i]['condition_dev_with_plan'];
                    $envAnalysis->impact_size_difference = $analysis[$i]['impact_size_difference'];
                    $envAnalysis->save();

                    $important_trait = $analysis[$i]['important_trait'];

                    for ($a = 0; $a < count($important_trait); $a++) {
                        $detail = new ImpactAnalysisDetail();
                        $detail->id_env_impact_analysis = $envAnalysis->id;
                        $detail->id_important_trait = $important_trait[$a]['id'];
                        $detail->important_trait = $important_trait[$a]['important_trait'];
                        $detail->description = $important_trait[$a]['desc'];
                        $detail->save();
                    }
                }
            }
        }

        if ($request->type == 'new') {
            // === WORKFLOW === //
            $project = Project::findOrFail($request->idProject);
            if($project->marking == 'amdal.form-ka-ba-signed') {
                $project->workflow_apply('draft-amdal-andal');
                $project->save();
            }
        } else {
            $lastTime = EnvImpactAnalysis::whereIn('id_impact_identifications', $ids)->orderBy('updated_at', 'DESC')->first()
                ->updated_at->locale('id')->diffForHumans();

            return 'Diperbarui ' . $lastTime;
        }

        return 'Diperbarui ' . EnvImpactAnalysis::orderBy('id', 'DESC')->first()->updated_at->locale('id')->diffForHumans();
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

    private function checkWorkspace($id_project)
    {
        $count_1 = EnvImpactAnalysis::whereHas('impactIdentification', function($q) use($id_project) {
            $q->where('id_project', $id_project);
        })
        ->count();

        if($count_1 == 0) {
            return false;
        }

        $count_2 = EnvImpactAnalysis::whereHas('impactIdentification', function($q) use($id_project) {
            $q->where('id_project', $id_project);
        })
        ->where('condition_dev_no_plan', '!=', null)
        ->count();

        return $count_1 === $count_2;
    }

    private function getImpactNotifications($id_project, $stages)
    {
        $alphabet_list = 'A';

        $impactIdentifications = ImpactIdentificationClone::select('id', 'id_project', 'id_project_component', 'id_change_type', 'id_project_rona_awal', 'is_hypothetical_significant')->where([['id_project', $id_project], ['is_hypothetical_significant', true]])->get();

        $important_trait = ImportantTrait::select('id', 'description')->get();
        $traits = [];

        foreach ($important_trait as $it) {
            $traits[] = [
                'id' => $it->id,
                'description' => $it->description,
                'desc' => null,
                'important_trait' => null
            ];
        }


        $results = [];

        foreach ($stages as $s) {
            $results[] = [
                'id' => $s->id,
                'name' => $alphabet_list . '. ' . $s->name,
                'type' => 'title'
            ];

            $total = 0;

            foreach ($impactIdentifications as $imp) {
                $ronaAwal = '';
                $component = '';

                // check stages
                $id_stages = null;

                if ($imp->projectComponent) {
                    $id_stages = $imp->projectComponent->component->id_project_stage;

                    if ($id_stages == $s->id) {
                        if ($imp->projectRonaAwal) {
                            $ronaAwal = $imp->projectRonaAwal->rona_awal->name;
                            $component = $imp->projectComponent->component->name;
                        } else {
                            continue;
                        }
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }


                $changeType = $imp->id_change_type ? $imp->changeType->name : '';

                $comments = $this->getComments($imp->id);

                $results[] = [
                    'id' => $imp->id,
                    'stage' => ucwords(strtolower($s->name)),
                    'name' => "$changeType $ronaAwal akibat $component",
                    'type' => 'subtitle',
                    'component' => $component,
                    'ronaAwal' => $ronaAwal,
                    'important_trait' => $traits,
                    'impact_type' => null,
                    'impact_eval_result' => null,
                    'studies_condition' => null,
                    'condition_dev_no_plan' => null,
                    'condition_dev_with_plan' => null,
                    'impact_size_difference' => null,
                    'dph' => $imp->is_hypothetical_significant === true ? 'DPH' : 'Tidak DPH',
                    'comments' => $comments
                ];
                $results[] = [
                    'id' => $imp->id,
                    'type' => 'comments'
                ];
                $total++;
            }

            if ($total == 0) {
                array_pop($results);
            } else {
                $alphabet_list++;
            }
        }

        return $results;
    }

    private function getEnvImpactAnalysis($id_project, $stages)
    {
        $alphabet_list = 'A';

        $impactIdentifications = ImpactIdentificationClone::select('id', 'id_project', 'id_project_component', 'id_change_type', 'id_project_rona_awal', 'is_hypothetical_significant')->where([['id_project', $id_project], ['is_hypothetical_significant', true]])->get();
        $results = [];
        $important_trait = ImportantTrait::select('id', 'description')->get();
        $traits = [];

        foreach ($important_trait as $it) {
            $traits[] = [
                'id' => $it->id,
                'description' => $it->description,
                'desc' => null,
                'important_trait' => null
            ];
        }

        foreach ($stages as $s) {
            $results[] = [
                'id' => $s->id,
                'name' =>  $alphabet_list . '. ' . $s->name,
                'type' => 'title'
            ];

            $total = 0;

            foreach ($impactIdentifications as $imp) {
                $ronaAwal = '';
                $component = '';

                // check stages
                $id_stages = null;

                if ($imp->projectComponent) {
                    $id_stages = $imp->projectComponent->component->id_project_stage;

                    if ($id_stages == $s->id) {
                        if ($imp->projectRonaAwal) {
                            $ronaAwal = $imp->projectRonaAwal->rona_awal->name;
                            $component = $imp->projectComponent->component->name;
                        } else {
                            continue;
                        }
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }

                $changeType = $imp->id_change_type ? $imp->changeType->name : '';

                $comments = $this->getComments($imp->id);

                $important_trait = [];

                if($imp->envImpactAnalysis) {
                    if($imp->envImpactAnalysis->detail->first()) {
                        foreach ($imp->envImpactAnalysis->detail as $det) {
                            $important_trait[] = [
                                'id' => $det->id_important_trait,
                                'description' => $det->importantTrait->description,
                                'desc' => $det->description,
                                'important_trait' => $det->important_trait
                            ];
                        }
                    } else {
                        $important_trait = $traits;
                    }
                } else {
                    $important_trait = $traits;
                }

                $results[] = [
                    'id' => $imp->id,
                    'stage' => ucwords(strtolower($s->name)),
                    'name' => "$changeType $ronaAwal akibat $component",
                    'type' => 'subtitle',
                    'component' => $component,
                    'ronaAwal' => $ronaAwal,
                    'important_trait' => $important_trait,
                    'impact_type' => $imp->envImpactAnalysis ? $imp->envImpactAnalysis->impact_type : null,
                    'impact_eval_result' => $imp->envImpactAnalysis ? $imp->envImpactAnalysis->impact_eval_result : null,
                    'studies_condition' => $imp->envImpactAnalysis ? $imp->envImpactAnalysis->studies_condition : null,
                    'condition_dev_no_plan' => $imp->envImpactAnalysis ? $imp->envImpactAnalysis->condition_dev_no_plan : null,
                    'condition_dev_with_plan' => $imp->envImpactAnalysis ? $imp->envImpactAnalysis->condition_dev_with_plan : null,
                    'impact_size_difference' => $imp->envImpactAnalysis ? $imp->envImpactAnalysis->impact_size_difference : null,
                    'dph' => $imp->is_hypothetical_significant === true ? 'DPH' : 'Tidak DPH',
                    'comments' => $comments
                ];

                $results[] = [
                    'id' => $imp->id,
                    'type' => 'comments'
                ];
                $total++;
            }

            if ($total == 0) {
                array_pop($results);
            } else {
                $alphabet_list++;
            }
        }

        return $results;
    }

    private function dokumen($id_project)
    {
        if (!Storage::disk('public')->exists('workspace')) {
            Storage::disk('public')->makeDirectory('workspace');
        }

        // CHECK IF DOCUMENT ALREADY EXIST
        $document_attachment = DocumentAttachment::where([['id_project', $id_project],['type', 'Dokumen Andal']])->first();
        if($document_attachment) {
            return response()->json(['message' => 'success']);
        }

        $project = Project::findOrFail($id_project);

        // === NOTIFICATION === //
        $receiver = [];
        // 1. Pemrakarsa
        $pemrakarsa_user = User::where('email', $project->initiator->email)->first();
        if($pemrakarsa_user) {
            $receiver[] = $pemrakarsa_user;
        }
        // 2. Penyusun
        $formulator_team_members = FormulatorTeamMember::whereHas('team', function($q) use($project) {
            $q->where('id_project', $project->id);
        })->get();
        foreach($formulator_team_members as $ftm) {
            if($ftm->formulator) {
                $formulator_user = User::where('email', $ftm->formulator->email)->first();
                if($formulator_user) {
                    if($ftm->position == 'Ketua' || $ftm->position == 'Anggota') {
                        $receiver[] = $formulator_user;
                    }
                }
            }
        }

        if(count($receiver) > 0) {
            Notification::send($receiver, new RklRPlNotification($project));
        }

        $save_file_name = $id_project . '-andal' . '.docx';

        Carbon::setLocale('id');

        // ======== TIM PENYUSUN AMDAL ========= //
        $formulator = [];
        $experts = [];
        $sub_project_scale_block = [];
        $total_sub_project_scale = 1;

        if($project->listSubProject) {
            if($project->listSubProject->first()) {
                foreach($project->listSubProject as $li) {
                    $sub_project_scale_block[] = [
                        'desc' => str_replace('&', 'dan', $total_sub_project_scale . '. ' . $li->name . ' Seluas ' . $li->scale . ' ' . $li->scale_unit)
                    ];
                    $total_sub_project_scale++;
                }
            }
        }

        $formulator_team = FormulatorTeam::where('id_project', $id_project)->first();
        $formulator_team_chairman = '';
        $formulator_team_date_start = '';
        $formulator_team_date_end = '';
        $formulator_no_reg = '';
        $formulator_team_period = '';
        if ($formulator_team) {
            $total_formulator = 1;
            $total_ahli = 1;
            foreach ($formulator_team->member as $m) {
                $formulatorName = '';
                $formulatorStatus = '';
                $formulatorRegNo = '';
                $formulatorExpired = '';
                $formulatorExpertise = '';

                if ($m->formulator) {
                    $formulatorName = $m->formulator->name;
                    $formulatorStatus = $m->formulator->membership_status;
                    $formulatorRegNo = $m->formulator->reg_no;
                    $formulatorExpired = $m->formulator->date_end ? Carbon::createFromFormat('Y-m-d H:i:s', $m->formulator->date_end)->isoFormat('D MMMM Y') : '';
                    $formulatorExpertise = str_replace('&', 'dan', $m->formulator->expertise ?? '');
                    if($m->position == 'Ketua') {
                        $formulator_team_chairman = $formulatorName;
                        $formulator_no_reg = $formulatorRegNo;
                        $formulator_team_date_start = $m->formulator->date_start ? Carbon::createFromFormat('Y-m-d H:i:s', $m->formulator->date_start)->isoFormat('D MMMM Y') : '';
                        $formulator_team_date_end = $formulatorExpired;
                        $formulator_team_period = $this->dateDifference($m->formulator_date_start, $m->formulator_date_end);
                    }
                    $formulator[] = [
                        'tim_penyusun' => $total_formulator,
                        'name' => $formulatorName,
                        'position' => $m->position ? $m->position : '',
                        'cert_type' => $formulatorStatus,
                        'reg_no' => $formulatorRegNo,
                        'cert_expire' => $formulatorExpired,
                        'expertise' => $formulatorExpertise,
                    ];
                    $total_formulator++;
                } else if ($m->expert) {
                    $formulatorName = $m->expert->name;
                    $formulatorStatus = $m->expert->status;
                    $formulatorExpertise = $m->expert->expertise;
                    $experts[] = [
                        'tim_ahli' => $total_ahli,
                        'name' => $formulatorName,
                        'position' => $m->expert->status == 'Asisten' ? $m->expert->status : 'Ahli',
                        'expertise' => str_replace('&', 'dan', $m->expert->expertise ?? 'dan')
                    ];
                    $total_ahli++;
                }
            }
        }

        // ========= LPJP ============= //
        $lpjp = [
            'name' => '',
            'address' => '',
            'reg_no' => '',
            'date_start' => '',
            'date_end' => '',
            'period' => '',
            'telephone' => '',
            'faksimili' => '',
            'pic' => '',
            'position' => ''
        ];
        $lpjp_data = Lpjp::where('id', $project->id_lpjp)->first();
        if ($lpjp_data) {
            $date_start = $lpjp_data->date_start ? Carbon::createFromFormat('Y-m-d H:i:s', $lpjp_data->date_start) : '';
            $date_end = $lpjp_data->date_end ? Carbon::createFromFormat('Y-m-d H:i:s', $lpjp_data->date_end) : '';
            $lpjp = [
                'name' => str_replace('&', 'dan', $lpjp_data->name ?? ''),
                'address' => str_replace('&', 'dan',  $lpjp_data->address . ', ' . $lpjp_data->district->name . ', Provinsi ' . $lpjp_data->province->name),
                'reg_no' => $lpjp_data->reg_no,
                'date_start' => $date_start->isoFormat('D MMMM Y'),
                'date_end' => $date_end->isoFormat('D MMMM Y'),
                'period' => $this->dateDifference($lpjp_data->date_start, $lpjp_data->date_end),
                'telephone' => $lpjp_data->phone_no,
                'faksimili' => '',
                'pic' => $lpjp_data->pic,
                'position' => ''
            ];
        }

        // ======== TIM PENYUSUN ======== //
        $penyusun = '';
        if(($project->type_formulator_team == 'lpjp') && $lpjp_data) {
            $penyusun = 'Lembaga Penyedia Jasa Penyusun Dokumen AMDAL (LPJP) ' . $lpjp['name'];
        } else if($project->type_formulator_team === 'mandiri') {
            $penyusun = 'Penyusun Perseorangan yang diketuai oleh ' . $formulator_team_chairman;
        }

        $penyusun_name = '';
        $penyusun_no_reg = '';
        $penyusun_date_start = '';
        $penyusun_date_end = '';
        $penyusun_period = '';
        $penyusun_address = '';
        $penyusun_phone = '';
        $lpjp_pj_block = [];
        if($project->type_formulator_team == 'lpjp') {
            $penyusun_name = $lpjp['name'];
            $penyusun_no_reg = $lpjp['reg_no'];
            $penyusun_date_start = $lpjp['date_start'];
            $penyusun_date_end = $lpjp['date_end'];
            $penyusun_period = $lpjp['period'];
            $penyusun_address = $lpjp['address'];
            $penyusun_phone = $lpjp['telephone'];
            $lpjp_pj_block[] = [
                'pic' => $lpjp['pic']
            ];
        } else {
            $penyusun_name = $formulator_team_chairman;
            $penyusun_no_reg = $formulator_no_reg;
            $penyusun_date_start = $formulator_team_date_start;
            $penyusun_date_end = $formulator_team_date_end;
            $penyusun_period = $formulator_team_period;
        }

        $penyusun_type = $project->type_formulator_team == 'lpjp' ? 'LPJP' : 'Penyusun Perseorangan';

        // ============== KA DAN MATRIKS DPH ============= //
        $ids = [4, 1, 2, 3];
        $stages = ProjectStage::select('id', 'name')->get()->sortBy(function ($model) use ($ids) {
            return array_search($model->getKey(), $ids);
        });
        $impact_identification = ImpactIdentificationClone::where('id_project', $id_project)->with(['potentialImpactEvaluation', 'envImpactAnalysis.detail.importantTrait'])->get();

        $pk = [];
        $k = [];
        $o = [];
        $po = [];
        $dp_pk = [];
        $dp_pk_name = [];
        $dp_k = [];
        $dp_k_name = [];
        $dp_o = [];
        $dp_o_name = [];
        $dp_po = [];
        $dp_po_name = [];
        $dph_pk = [];
        $dph_k = [];
        $dph_o = [];
        $dph_po = [];
        $mdp_pk = [];
        $mdp_pk_name = [];
        $mdp_k = [];
        $mdp_k_name = [];
        $mdp_o = [];
        $mdp_o_name = [];
        $mdp_po = [];
        $mdp_po_name = [];
        $dph_pk = [];
        $dph_k = [];
        $dph_o = [];
        $dph_po = [];
        $mdph_pk = [];
        $mdph_pk_name = [];
        $mdph_k = [];
        $mdph_k_name = [];
        $mdph_o = [];
        $mdph_o_name = [];
        $mdph_po = [];
        $mdph_po_name = [];
        $bwk_pk = [];
        $bwk_k = [];
        $bwk_o = [];
        $bwk_po = [];
        $com_pk = [];
        $com_pk_name = [];
        $com_k = [];
        $com_k_name = [];
        $com_o = [];
        $com_o_name = [];
        $com_po = [];
        $com_po_name = [];
        $com_replace = [];
        $pk_bwk = [];
        $k_bwk = [];
        $o_bwk = [];
        $po_bwk = [];
        $dpg_pk_block = [];
        $dpg_pk_block_name = [];
        $dpg_k_block = [];
        $dpg_k_block_name = [];
        $dpg_o_block = [];
        $dpg_o_block_name = [];
        $dpg_po_block = [];
        $dpg_po_block_name = [];
        $gfk_rona_block = [];
        $b_rona_block = [];
        $seb_rona_block = [];
        $kk_rona_block = [];
        $ru_pk = [];
        $ru_pk_name = [];
        $ru_k = [];
        $ru_k_name = [];
        $ru_o = [];
        $ru_o_name = [];
        $ru_po = [];
        $ru_po_name = [];
        $kls_block = [];
        $ed_replace = [];
        $initial_study_plan_replace = [];

        foreach ($stages as $s) {
            $total = 1;
            $stage_id = null;
            foreach ($impact_identification as $imp) {
                $ronaAwal = '';
                $component = '';
                $stage_merge = '';

                if($stage_id == $s->id) {
                    $stage_merge = '<w:vMerge w:val="continue"/>';
                } else {
                    $stage_merge = '<w:vMerge w:val="restart"/>';
                }

                // check stages
                $id_stages = null;

                if ($imp->projectComponent) {
                    $id_stages = $imp->projectComponent->component->id_project_stage;

                    if ($id_stages == $s->id) {
                        if ($imp->projectRonaAwal) {
                            $ronaAwal = str_replace('&', 'dan', $imp->projectRonaAwal->rona_awal->name);
                            $component = str_replace('&', 'dan', $imp->projectComponent->component->name);
                        } else {
                            continue;
                        }
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }

                $component_type = $this->getComponentTypeImp($imp);
                $initial_study_plan = $this->renderHtmlTable($imp->initial_study_plan , 3500, 'Arial', '11');

                // ======= POTENTIAL IMPACT EVALUATIONS ======= //
                $ed_besaran_rencana = '';
                $ed_kondisi_rona = '';
                $ed_pengaruh_rencana = '';
                $ed_intensitas_perhatian = '';
                $ed_kesimpulan = '';

                if ($imp->potentialImpactEvaluation) {
                    foreach ($imp->potentialImpactEvaluation as $pI) {
                        if ($pI->id_pie_param == 1) {
                            $ed_besaran_rencana = '${edt_' . $pI->id_pie_param . $pI->id . '_' . $s->id . '}';
                            $ed_replace[] = [
                                'data' => $this->renderHtmlTable($pI->text, 3500, 'Arial', '11'),
                                'replace' => '${edt_' . $pI->id_pie_param . $pI->id . '_' . $s->id . '}'
                            ];
                        } else if ($pI->id_pie_param == 2) {
                            $ed_kondisi_rona = '${edt_' . $pI->id_pie_param . $pI->id . '_' . $s->id . '}';
                            $ed_replace[] = [
                                'data' => $this->renderHtmlTable($pI->text, 3500, 'Arial', '11'),
                                'replace' => '${edt_' . $pI->id_pie_param . $pI->id . '_' . $s->id . '}'
                            ];
                        } else if ($pI->id_pie_param == 3) {
                            $ed_pengaruh_rencana = '${edt_' . $pI->id_pie_param . $pI->id . '_' . $s->id . '}';
                            $ed_replace[] = [
                                'data' => $this->renderHtmlTable($pI->text, 3500, 'Arial', '11'),
                                'replace' => '${edt_' . $pI->id_pie_param . $pI->id . '_' . $s->id . '}'
                            ];
                        } else if ($pI->id_pie_param == 4) {
                            $ed_intensitas_perhatian = '${edt_' . $pI->id_pie_param . $pI->id . '_' . $s->id . '}';
                            $ed_replace[] = [
                                'data' => $this->renderHtmlTable($pI->text, 3500, 'Arial', '11'),
                                'replace' => '${edt_' . $pI->id_pie_param . $pI->id . '_' . $s->id . '}'
                            ];
                        } else if ($pI->id_pie_param == 5) {
                            $ed_kesimpulan = '${edt_' . $pI->id_pie_param . $pI->id . '_' . $s->id . '}';
                            $ed_replace[] = [
                                'data' => $this->renderHtmlTable($pI->text, 3500, 'Arial', '11'),
                                'replace' => '${edt_' . $pI->id_pie_param . $pI->id . '_' . $s->id . '}'
                            ];
                        }
                    }
                }

                $change_type = $imp->id_change_type ? $imp->changeType->name : '';

                // PENENTUAN SIFAT PENTING DAMPAK
                $impact_result = '';
                $table_with_no_plan = [
                    'studies' => '',
                    'no_plan' => '',
                    'with_plan' => '',
                    'size_differ' => ''
                ];
                $table_with_no_plan_data = [
                    'studies' => '',
                    'no_plan' => '',
                    'with_plan' => '',
                    'size_differ' => ''
                ];
                $important_trait_1 = ['nilai' => '', 'keterangan' => ''];
                $important_trait_2 = ['nilai' => '', 'keterangan' => ''];
                $important_trait_3 = ['nilai' => '', 'keterangan' => ''];
                $important_trait_4 = ['nilai' => '', 'keterangan' => ''];
                $important_trait_5 = ['nilai' => '', 'keterangan' => ''];
                $important_trait_6 = ['nilai' => '', 'keterangan' => ''];
                $important_trait_7 = ['nilai' => '', 'keterangan' => ''];

                $important_trait_1_data = ['keterangan' => ''];
                $important_trait_2_data = ['keterangan' => ''];
                $important_trait_3_data = ['keterangan' => ''];
                $important_trait_4_data = ['keterangan' => ''];
                $important_trait_5_data = ['keterangan' => ''];
                $important_trait_6_data = ['keterangan' => ''];
                $important_trait_7_data = ['keterangan' => ''];

                $impact_result = '';
                if($imp->is_hypothetical_significant) {
                    if($imp->envImpactAnalysis) {
                        $impact_result = $imp->envImpactAnalysis->impact_eval_result ?? '';
                        $table_with_no_plan = [
                            'studies' => '${st_' . $s->id . '_' . $imp->id . '}',
                            'no_plan' => '${no_' . $s->id . '_' . $imp->id . '}',
                            'with_plan' => '${wi_' . $s->id . '_' . $imp->id . '}',
                            'size_differ' => '${si_' . $s->id . '_' . $imp->id . '}',
                        ];
                        $table_with_no_plan_data = [
                            'studies' => $this->renderHtmlTable($imp->envImpactAnalysis->studies_condition, 4900, 'Arial', '13.5'),
                            'no_plan' => $this->renderHtmlTable($imp->envImpactAnalysis->condition_dev_no_plan, 4900, 'Arial', '13.5'),
                            'with_plan' => $this->renderHtmlTable($imp->envImpactAnalysis->condition_dev_with_plan, 4900, 'Arial', '13.5'),
                            'size_differ' => $this->renderHtmlTable($imp->envImpactAnalysis->impact_size_difference, 4900, 'Arial', '13.5')
                        ];
                        if($imp->envImpactAnalysis->detail) {
                            if($imp->envImpactAnalysis->detail->first()) {
                                foreach($imp->envImpactAnalysis->detail as $det) {
                                    if($det->id_important_trait == 1) {
                                        $important_trait_1['nilai'] = $det->important_trait;
                                        $important_trait_1['keterangan'] = '${ket_' . $imp->envImpactAnalysis->id . '_' . $det->id . '}';
                                        $important_trait_1_data['keterangan'] = $this->renderHtmlTable($det->description, 3500, 'Arial', '13.5');
                                    } else if($det->id_important_trait == 2) {
                                        $important_trait_2['nilai'] = $det->important_trait;
                                        $important_trait_2['keterangan'] = '${ket_' . $imp->envImpactAnalysis->id . '_' . $det->id . '}';
                                        $important_trait_2_data['keterangan'] = $this->renderHtmlTable($det->description, 3500, 'Arial', '13.5');
                                    } else if($det->id_important_trait == 3) {
                                        $important_trait_3['nilai'] = $det->important_trait;
                                        $important_trait_3['keterangan'] = '${ket_' . $imp->envImpactAnalysis->id . '_' . $det->id . '}';
                                        $important_trait_3_data['keterangan'] = $this->renderHtmlTable($det->description, 3500, 'Arial', '13.5');
                                    } else if($det->id_important_trait == 4) {
                                        $important_trait_4['nilai'] = $det->important_trait;
                                        $important_trait_4['keterangan'] = '${ket_' . $imp->envImpactAnalysis->id . '_' . $det->id . '}';
                                        $important_trait_4_data['keterangan'] = $this->renderHtmlTable($det->description, 3500, 'Arial', '13.5');
                                    } else if($det->id_important_trait == 5) {
                                        $important_trait_5['nilai'] = $det->important_trait;
                                        $important_trait_5['keterangan'] = '${ket_' . $imp->envImpactAnalysis->id . '_' . $det->id . '}';
                                        $important_trait_5_data['keterangan'] = $this->renderHtmlTable($det->description, 3500, 'Arial', '13.5');
                                    } else if($det->id_important_trait == 6) {
                                        $important_trait_6['nilai'] = $det->important_trait;
                                        $important_trait_6['keterangan'] = '${ket_' . $imp->envImpactAnalysis->id . '_' . $det->id . '}';
                                        $important_trait_6_data['keterangan'] = $this->renderHtmlTable($det->description, 3500, 'Arial', '13.5');
                                    } else if($det->id_important_trait == 7) {
                                        $important_trait_7['nilai'] = $det->important_trait;
                                        $important_trait_7['keterangan'] = '${ket_' . $imp->envImpactAnalysis->id . '_' . $det->id . '}';
                                        $important_trait_7_data['keterangan'] = $this->renderHtmlTable($det->description, 3500, 'Arial', '13.5');
                                    }
                                }
                            }
                        }
                    }
                }

                if ($s->name == 'Pra Konstruksi') {
                    // MATRIKS DP
                    if (!in_array($component, $dp_pk_name)) {
                        $dp_pk_name[] = $component;
                        $dp_pk[] = [
                            'dp_pk' => 'Tahap ' . $s->name . $stage_merge,
                            'dp_pk_component' => $component
                        ];
                    }

                    // MATRIKS DAMPAK PENTING
                    if (!in_array($component, $mdp_pk_name)) {
                        $mdp_pk_name[] = $component;
                        $mdp_pk[] = [
                            'mdp_pk' => 'Tahap ' . $s->name . $stage_merge,
                            'mdp_pk_component' => $component
                        ];
                    }

                    // JADWAL RENCANA USAHA DAN/ATAU KEGIATAN
                    if (!in_array($component, $ru_pk_name)) {
                        $ru_pk_name[] = $component;
                        $ru_pk[] = [
                            'ru_pk' => count($ru_pk) + 1 . '.',
                            'ru_pk_component' => $component
                        ];
                    }

                    // MATRIKS DPH & COMPONENT
                    if (!in_array($component, $mdph_pk_name)) {
                        $mdph_pk_name[] = $component;
                        $mdph_pk[] = [
                            'mdph_pk' => 'Tahap ' . $s->name . $stage_merge,
                            'mdph_pk_component' => $component
                        ];
                    }

                    // PRAKIRAAN DAMPAK PENTING
                    if($imp->is_hypothetical_significant) {
                        if(!in_array($component, $dpg_pk_block_name)) {

                            $dpg_pk_block_name[] = $component;
                            $dpg_pk_block[] = [
                                'dpg_pk_component' => $component,
                                'dpg_pk_imp_block' => '${dpg_pk_imp_block_'.count($dpg_pk_block_name).'}',
                                '/dpg_pk_imp_block' => '${/dpg_pk_imp_block_'.count($dpg_pk_block_name).'}',
                                'dampak' => [$change_type . ' ' . $ronaAwal],
                                'table_with_no_plan' => [$table_with_no_plan],
                                'table_with_no_plan_data' => [$table_with_no_plan_data],
                                'impact_result' => [strtolower($impact_result)],
                                'important_trait' => [
                                    [
                                        $important_trait_1,
                                        $important_trait_2,
                                        $important_trait_3,
                                        $important_trait_4,
                                        $important_trait_5,
                                        $important_trait_6,
                                        $important_trait_7
                                    ]
                                ],
                                'important_trait_data' => [
                                    [
                                        $important_trait_1_data,
                                        $important_trait_2_data,
                                        $important_trait_3_data,
                                        $important_trait_4_data,
                                        $important_trait_5_data,
                                        $important_trait_6_data,
                                        $important_trait_7_data
                                    ]
                                ],
                            ];
                        } else {
                            $index = array_search($component, $dpg_pk_block_name);
                            $dpg_pk_block[$index]['dampak'][] = $change_type . ' ' . $ronaAwal;
                            $dpg_pk_block[$index]['table_with_no_plan'][] = $table_with_no_plan;
                            $dpg_pk_block[$index]['table_with_no_plan_data'][] = $table_with_no_plan_data;
                            $dpg_pk_block[$index]['impact_result'][] = strtolower($impact_result);
                            $dpg_pk_block[$index]['important_trait'][] = [
                                $important_trait_1,
                                $important_trait_2,
                                $important_trait_3,
                                $important_trait_4,
                                $important_trait_5,
                                $important_trait_6,
                                $important_trait_7
                            ];
                            $dpg_pk_block[$index]['important_trait_data'][] = [
                                $important_trait_1_data,
                                $important_trait_2_data,
                                $important_trait_3_data,
                                $important_trait_4_data,
                                $important_trait_5_data,
                                $important_trait_6_data,
                                $important_trait_7_data
                            ];
                        }

                        // DESKRIPSI BATAS WILAYAH STUDI
                        if(strtolower($component_type) == 'geofisik kimia' || strtolower($component_type) == 'biologi') {
                            $pk_bwk[] = [
                                'pk_bwk' => $ronaAwal . ' akibat ' . $component,
                                'pk_bwk_ba' => ''
                            ];
                        }
                    }

                    $pk[] = [
                        'ka_pk' => $total,
                        'dp_pk' => $total,
                        'ka_pk_component' => $component,
                        'ka_pk_plan' => '${pk_' . $imp->id . '_study_plan}',
                        'ka_pk_rona_awal' => $ronaAwal,
                        'ka_pk_hypothetical' => $imp->is_hypothetical_significant ? 'DPH' : 'Tidak DPH',
                        'ka_pk_study_location' => str_replace('&', 'dan', $imp->study_location ?? ''),
                        'ka_pk_study_length' => $imp->study_length_year . ' tahun ' . $imp->study_length_month . ' bulan',
                        'ka_pk_potential_impact' => "$change_type $ronaAwal akibat $component",
                        'ka_pk_ed_besaran_rencana' => $ed_besaran_rencana,
                        'ka_pk_ed_kondisi_rona' => $ed_kondisi_rona,
                        'ka_pk_ed_pengaruh_rencana' => $ed_pengaruh_rencana,
                        'ka_pk_ed_intensitas_perhatian' => $ed_intensitas_perhatian,
                        'ka_pk_ed_kesimpulan' => $ed_kesimpulan,
                        'ka_pk_env_impact' => "$change_type $ronaAwal"
                    ];
                    $initial_study_plan_replace[] = ['replace' => '${pk_' . $imp->id . '_study_plan}' , 'data' => $initial_study_plan];

                    // MATRIKS DPH
                    $dph_pk[] = [
                        'dph_pk' => $total,
                        'dph_pk_component' => $component,
                        'dph_pk_unit' => str_replace('&', 'dan', $imp->projectComponent->measurement ?? ''),
                        'dph_pk_plan' => '${dph_pk_' . $imp->id . '_study_plan}',
                        'dph_pk_rona_awal' => $ronaAwal,
                        'dph_pk_hypothetical' => $imp->is_hypothetical_significant ? 'DPH' : 'Tidak DPH',
                        'dph_pk_study_location' => str_replace('&', 'dan', $imp->study_location ?? ''),
                        'dph_pk_study_length' => $imp->study_length_year . ' tahun ' . $imp->study_length_month . ' bulan',
                        'dph_pk_potential_impact' => "$change_type $ronaAwal akibat $component",
                        'dph_pk_ed_besaran_rencana' => $ed_besaran_rencana,
                        'dph_pk_ed_kondisi_rona' => $ed_kondisi_rona,
                        'dph_pk_ed_pengaruh_rencana' => $ed_pengaruh_rencana,
                        'dph_pk_ed_intensitas_perhatian' => $ed_intensitas_perhatian,
                        'dph_pk_ed_kesimpulan' => $ed_kesimpulan,
                        'dph_pk_env_impact' => "$change_type $ronaAwal"
                    ];
                    $initial_study_plan_replace[] = ['replace' => '${dph_pk_' . $imp->id . '_study_plan}' , 'data' => $initial_study_plan];

                    // BATAS WAKTU KAJIAN
                    $bwk_pk[] = [
                        'bwk_pk' => $total,
                        'bwk_pk_component' => $component,
                        'bwk_pk_env_impact' => "$change_type $ronaAwal",
                        'bwk_pk_study_length' => $imp->study_length_year . ' tahun ' . $imp->study_length_month . ' bulan',
                    ];
                } else if ($s->name == 'Konstruksi') {
                    // MATRIKS DP
                    if (!in_array($component, $dp_k_name)) {
                        $dp_k_name[] = $component;
                        $dp_k[] = [
                            'dp_k' => 'Tahap ' . $s->name . $stage_merge,
                            'dp_k_component' => $component
                        ];
                    }

                    // MATRIKS DAMPAK PENTING
                    if (!in_array($component, $mdp_k_name)) {
                        $mdp_k_name[] = $component;
                        $mdp_k[] = [
                            'mdp_k' => 'Tahap ' . $s->name . $stage_merge,
                            'mdp_k_component' => $component
                        ];
                    }

                    // JADWAL RENCANA USAHA DAN/ATAU KEGIATAN
                    if (!in_array($component, $ru_k_name)) {
                        $ru_k_name[] = $component;
                        $ru_k[] = [
                            'ru_k' => count($ru_k) + 1 . '.',
                            'ru_k_component' => $component
                        ];
                    }

                    // MATRIKS DPH
                    if (!in_array($component, $mdph_k_name)) {
                        $mdph_k_name[] = $component;
                        $mdph_k[] = [
                            'mdph_k' => 'Tahap ' . $s->name . $stage_merge,
                            'mdph_k_component' => $component
                        ];
                    }

                    // PRAKIRAAN DAMPAK PENTING
                    if($imp->is_hypothetical_significant) {
                        if(!in_array($component, $dpg_k_block_name)) {
                            $dpg_k_block_name[] = $component;
                            $dpg_k_block[] = [
                                'dpg_k_component' => $component,
                                'dpg_k_imp_block' => '${dpg_k_imp_block_'.count($dpg_k_block_name).'}',
                                '/dpg_k_imp_block' => '${/dpg_k_imp_block_'.count($dpg_k_block_name).'}',
                                'dampak' => [$change_type . ' ' . $ronaAwal],
                                'table_with_no_plan' => [$table_with_no_plan],
                                'table_with_no_plan_data' => [$table_with_no_plan_data],
                                'impact_result' => [strtolower($impact_result)],
                                'important_trait' => [
                                    [
                                        $important_trait_1,
                                        $important_trait_2,
                                        $important_trait_3,
                                        $important_trait_4,
                                        $important_trait_5,
                                        $important_trait_6,
                                        $important_trait_7
                                    ]
                                ],
                                'important_trait_data' => [
                                    [
                                        $important_trait_1_data,
                                        $important_trait_2_data,
                                        $important_trait_3_data,
                                        $important_trait_4_data,
                                        $important_trait_5_data,
                                        $important_trait_6_data,
                                        $important_trait_7_data
                                    ]
                                ]
                            ];
                        } else {
                            $index = array_search($component, $dpg_k_block_name);
                            $dpg_k_block[$index]['dampak'][] = $change_type . ' ' . $ronaAwal;
                            $dpg_k_block[$index]['table_with_no_plan'][] = $table_with_no_plan;
                            $dpg_k_block[$index]['table_with_no_plan_data'][] = $table_with_no_plan_data;
                            $dpg_k_block[$index]['impact_result'][] = strtolower($impact_result);
                            $dpg_k_block[$index]['important_trait'][] = [
                                $important_trait_1,
                                $important_trait_2,
                                $important_trait_3,
                                $important_trait_4,
                                $important_trait_5,
                                $important_trait_6,
                                $important_trait_7
                            ];
                            $dpg_k_block[$index]['important_trait_data'][] = [
                                $important_trait_1_data,
                                $important_trait_2_data,
                                $important_trait_3_data,
                                $important_trait_4_data,
                                $important_trait_5_data,
                                $important_trait_6_data,
                                $important_trait_7_data
                            ];
                        }
                    }

                    // DESKRIPSI BATAS WILAYAH STUDI
                    if(strtolower($component_type) == 'geofisik kimia' || strtolower($component_type) == 'biologi') {
                        $k_bwk[] = [
                            'k_bwk' => $ronaAwal . ' akibat ' . $component,
                            'k_bwk_ba' => ''
                        ];
                    }

                    $k[] = [
                        'ka_k' => $total,
                        'ka_k_component' => $component,
                        'ka_k_plan' => '${k_' . $imp->id . '_study_plan}',
                        'ka_k_rona_awal' => $ronaAwal,
                        'ka_k_hypothetical' => $imp->is_hypothetical_significant ? 'DPH' : 'Tidak DPH',
                        'ka_k_study_location' => str_replace('&', 'dan', $imp->study_location ?? ''),
                        'ka_k_study_length' => $imp->study_length_year . ' tahun ' . $imp->study_length_month . ' bulan',
                        'ka_k_potential_impact' => "$change_type $ronaAwal akibat $component",
                        'ka_k_ed_besaran_rencana' => $ed_besaran_rencana,
                        'ka_k_ed_kondisi_rona' => $ed_kondisi_rona,
                        'ka_k_ed_pengaruh_rencana' => $ed_pengaruh_rencana,
                        'ka_k_ed_intensitas_perhatian' => $ed_intensitas_perhatian,
                        'ka_k_ed_kesimpulan' => $ed_kesimpulan,
                        'ka_k_env_impact' => "$change_type $ronaAwal"
                    ];
                    $initial_study_plan_replace[] = ['replace' => '${k_' . $imp->id . '_study_plan}' , 'data' => $initial_study_plan];

                    // MATRIKS DPH
                    $dph_k[] = [
                        'dph_k' => $total,
                        'dph_k_component' => $component,
                        'dph_k_unit' => str_replace('&', 'dan', $imp->projectComponent->measurement ?? ''),
                        'dph_k_plan' => '${dph_k_' . $imp->id . '_study_plan}',
                        'dph_k_rona_awal' => $ronaAwal,
                        'dph_k_hypothetical' => $imp->is_hypothetical_significant ? 'DPH' : 'Tidak DPH',
                        'dph_k_study_location' => str_replace('&', 'dan', $imp->study_location ?? ''),
                        'dph_k_study_length' => $imp->study_length_year . ' tahun ' . $imp->study_length_month . ' bulan',
                        'dph_k_potential_impact' => "$change_type $ronaAwal akibat $component",
                        'dph_k_ed_besaran_rencana' => $ed_besaran_rencana,
                        'dph_k_ed_kondisi_rona' => $ed_kondisi_rona,
                        'dph_k_ed_pengaruh_rencana' => $ed_pengaruh_rencana,
                        'dph_k_ed_intensitas_perhatian' => $ed_intensitas_perhatian,
                        'dph_k_ed_kesimpulan' => $ed_kesimpulan,
                        'dph_k_env_impact' => "$change_type $ronaAwal"
                    ];
                    $initial_study_plan_replace[] = ['replace' => '${dph_k_' . $imp->id . '_study_plan}' , 'data' => $initial_study_plan];

                    // BATAS WAKTU KAJIAN
                    $bwk_k[] = [
                        'bwk_k' => $total,
                        'bwk_k_component' => $component,
                        'bwk_k_env_impact' => "$change_type $ronaAwal",
                        'bwk_k_study_length' => $imp->study_length_year . ' tahun ' . $imp->study_length_month . ' bulan',
                    ];
                } else if ($s->name == 'Operasi') {
                    // MATRIKS DP
                    if (!in_array($component, $dp_o_name)) {
                        $dp_o_name[] = $component;
                        $dp_o[] = [
                            'dp_o' => 'Tahap ' . $s->name . $stage_merge,
                            'dp_o_component' => $component
                        ];
                    }

                    // MATRIKS DAMPAK PENTING
                    if (!in_array($component, $mdp_o_name)) {
                        $mdp_o_name[] = $component;
                        $mdp_o[] = [
                            'mdp_o' => 'Tahap ' . $s->name . $stage_merge,
                            'mdp_o_component' => $component
                        ];
                    }

                    // MATRIKS DPH
                    if (!in_array($component, $mdph_o_name)) {
                        $mdph_o_name[] = $component;
                        $mdph_o[] = [
                            'mdph_o' => 'Tahap ' . $s->name . $stage_merge,
                            'mdph_o_component' => $component
                        ];
                    }

                    // JADWAL RENCANA USAHA DAN/ATAU KEGIATAN
                    if (!in_array($component, $ru_o_name)) {
                        $ru_o_name[] = $component;
                        $ru_o[] = [
                            'ru_o' => count($ru_o) + 1 . '.',
                            'ru_o_component' => $component
                        ];
                    }


                    // PRAKIRAAN DAMPAK PENTING
                    if($imp->is_hypothetical_significant) {
                        if(!in_array($component, $dpg_o_block_name)) {
                            $dpg_o_block_name[] = $component;
                            $dpg_o_block[] = [
                                'dpg_o_component' => $component,
                                'dpg_o_imp_block' => '${dpg_o_imp_block_'.count($dpg_o_block_name).'}',
                                '/dpg_o_imp_block' => '${/dpg_o_imp_block_'.count($dpg_o_block_name).'}',
                                'dampak' => [$change_type . ' ' . $ronaAwal],
                                'table_with_no_plan' => [$table_with_no_plan],
                                'table_with_no_plan_data' => [$table_with_no_plan_data],
                                'impact_result' => [strtolower($impact_result)],
                                'important_trait' => [
                                    [
                                        $important_trait_1,
                                        $important_trait_2,
                                        $important_trait_3,
                                        $important_trait_4,
                                        $important_trait_5,
                                        $important_trait_6,
                                        $important_trait_7
                                    ]
                                ],
                                'important_trait_data' => [
                                    [
                                        $important_trait_1_data,
                                        $important_trait_2_data,
                                        $important_trait_3_data,
                                        $important_trait_4_data,
                                        $important_trait_5_data,
                                        $important_trait_6_data,
                                        $important_trait_7_data
                                    ]
                                ]
                            ];
                        } else {
                            $index = array_search($component, $dpg_o_block_name);
                            $dpg_o_block[$index]['dampak'][] = $change_type . ' ' . $ronaAwal;
                            $dpg_o_block[$index]['table_with_no_plan'][] = $table_with_no_plan;
                            $dpg_o_block[$index]['table_with_no_plan_data'][] = $table_with_no_plan_data;
                            $dpg_o_block[$index]['impact_result'][] = strtolower($impact_result);
                            $dpg_o_block[$index]['important_trait'][] = [
                                $important_trait_1,
                                $important_trait_2,
                                $important_trait_3,
                                $important_trait_4,
                                $important_trait_5,
                                $important_trait_6,
                                $important_trait_7
                            ];
                            $dpg_o_block[$index]['important_trait_data'][] = [
                                $important_trait_1_data,
                                $important_trait_2_data,
                                $important_trait_3_data,
                                $important_trait_4_data,
                                $important_trait_5_data,
                                $important_trait_6_data,
                                $important_trait_7_data
                            ];
                        }
                    }

                    // DESKRIPSI BATAS WILAYAH STUDI
                    if(strtolower($component_type) == 'geofisik kimia' || strtolower($component_type) == 'biologi') {
                        $o_bwk[] = [
                            'o_bwk' => $ronaAwal . ' akibat ' . $component,
                            'o_bwk_ba' => ''
                        ];
                    }

                    $o[] = [
                        'ka_o' => $total,
                        'ka_o_component' => $component,
                        'ka_o_plan' => '${o_' . $imp->id . '_study_plan}',
                        'ka_o_rona_awal' => $ronaAwal,
                        'ka_o_hypothetical' => $imp->is_hypothetical_significant ? 'DPH' : 'Tidak DPH',
                        'ka_o_study_location' => str_replace('&', 'dan', $imp->study_location ?? ''),
                        'ka_o_study_length' => $imp->study_length_year . ' tahun ' . $imp->study_length_month . ' bulan',
                        'ka_o_potential_impact' => "$change_type $ronaAwal akibat $component",
                        'ka_o_ed_besaran_rencana' => $ed_besaran_rencana,
                        'ka_o_ed_kondisi_rona' => $ed_kondisi_rona,
                        'ka_o_ed_pengaruh_rencana' => $ed_pengaruh_rencana,
                        'ka_o_ed_intensitas_perhatian' => $ed_intensitas_perhatian,
                        'ka_o_ed_kesimpulan' => $ed_kesimpulan,
                        'ka_o_env_impact' => "$change_type $ronaAwal"
                    ];
                    $initial_study_plan_replace[] = ['replace' => '${o_' . $imp->id . '_study_plan}' , 'data' => $initial_study_plan];

                    // MATRIKS DPH
                    $dph_o[] = [
                        'dph_o' => $total,
                        'dph_o_component' => $component,
                        'dph_o_unit' => str_replace('&', 'dan', $imp->projectComponent->measurement ?? ''),
                        'dph_o_plan' => '${dph_o_' . $imp->id . '_study_plan}',
                        'dph_o_rona_awal' => $ronaAwal,
                        'dph_o_hypothetical' => $imp->is_hypothetical_significant ? 'DPH' : 'Tidak DPH',
                        'dph_o_study_location' => str_replace('&', 'dan', $imp->study_location ?? ''),
                        'dph_o_study_length' => $imp->study_length_year . ' tahun ' . $imp->study_length_month . ' bulan',
                        'dph_o_potential_impact' => "$change_type $ronaAwal akibat $component",
                        'dph_o_ed_besaran_rencana' => $ed_besaran_rencana,
                        'dph_o_ed_kondisi_rona' => $ed_kondisi_rona,
                        'dph_o_ed_pengaruh_rencana' => $ed_pengaruh_rencana,
                        'dph_o_ed_intensitas_perhatian' => $ed_intensitas_perhatian,
                        'dph_o_ed_kesimpulan' => $ed_kesimpulan,
                        'dph_o_env_impact' => "$change_type $ronaAwal"
                    ];
                    $initial_study_plan_replace[] = ['replace' => '${dph_o_' . $imp->id . '_study_plan}' , 'data' => $initial_study_plan];

                    // BATAS WAKTU KAJIAN
                    $bwk_o[] = [
                        'bwk_o' => $total,
                        'bwk_o_component' => $component,
                        'bwk_o_env_impact' => "$change_type $ronaAwal",
                        'bwk_o_study_length' => $imp->study_length_year . ' tahun ' . $imp->study_length_month . ' bulan',
                    ];
                } else if ($s->name == 'Pasca Operasi') {
                    // MATRIKS DP
                    if (!in_array($component, $dp_po_name)) {
                        $dp_po_name[] = $component;
                        $dp_po[] = [
                            'dp_po' => 'Tahap ' . $s->name . $stage_merge,
                            'dp_po_component' => $component
                        ];
                    }

                    // MATRIKS DAMPAK PENTING
                    if (!in_array($component, $mdp_po_name)) {
                        $mdp_po_name[] = $component;
                        $mdp_po[] = [
                            'mdp_po' => 'Tahap ' . $s->name . $stage_merge,
                            'mdp_po_component' => $component
                        ];
                    }

                    // MATRIKS DPH
                    if (!in_array($component, $mdph_po_name)) {
                        $mdph_po_name[] = $component;
                        $mdph_po[] = [
                            'mdph_po' => 'Tahap ' . $s->name . $stage_merge,
                            'mdph_po_component' => $component
                        ];
                    }

                    // JADWAL RENCANA USAHA DAN/ATAU KEGIATAN
                    if (!in_array($component, $ru_po_name)) {
                        $ru_po_name[] = $component;
                        $ru_po[] = [
                            'ru_po' => count($ru_po) + 1 . '.',
                            'ru_po_component' => $component
                        ];
                    }

                    // PRAKIRAAN DAMPAK PENTING
                    if($imp->is_hypothetical_significant) {
                        if(!in_array($component, $dpg_po_block_name)) {
                            $dpg_po_block_name[] = $component;
                            $dpg_po_block[] = [
                                'dpg_po_component' => $component,
                                'dpg_po_imp_block' => '${dpg_po_imp_block_'.count($dpg_po_block_name).'}',
                                '/dpg_po_imp_block' => '${/dpg_po_imp_block_'.count($dpg_po_block_name).'}',
                                'dampak' => [$change_type . ' ' . $ronaAwal],
                                'table_with_no_plan' => [$table_with_no_plan],
                                'table_with_no_plan_data' => [$table_with_no_plan_data],
                                'impact_result' => [strtolower($impact_result)],
                                'important_trait' => [
                                    [
                                        $important_trait_1,
                                        $important_trait_2,
                                        $important_trait_3,
                                        $important_trait_4,
                                        $important_trait_5,
                                        $important_trait_6,
                                        $important_trait_7
                                    ]
                                ],
                                'important_trait_data' => [
                                    [
                                        $important_trait_1_data,
                                        $important_trait_2_data,
                                        $important_trait_3_data,
                                        $important_trait_4_data,
                                        $important_trait_5_data,
                                        $important_trait_6_data,
                                        $important_trait_7_data
                                    ]
                                ]
                            ];
                        } else {
                            $index = array_search($component, $dpg_po_block_name);
                            $dpg_po_block[$index]['dampak'][] = $change_type . ' ' . $ronaAwal;
                            $dpg_po_block[$index]['table_with_no_plan'][] = $table_with_no_plan;
                            $dpg_po_block[$index]['table_with_no_plan_data'][] = $table_with_no_plan_data;
                            $dpg_po_block[$index]['impact_result'][] = strtolower($impact_result);
                            $dpg_po_block[$index]['important_trait'][] = [
                                $important_trait_1,
                                $important_trait_2,
                                $important_trait_3,
                                $important_trait_4,
                                $important_trait_5,
                                $important_trait_6,
                                $important_trait_7
                            ];
                            $dpg_po_block[$index]['important_trait_data'][] = [
                                $important_trait_1_data,
                                $important_trait_2_data,
                                $important_trait_3_data,
                                $important_trait_4_data,
                                $important_trait_5_data,
                                $important_trait_6_data,
                                $important_trait_7_data
                            ];
                        }
                    }

                    // DESKRIPSI BATAS WILAYAH STUDI
                    if(strtolower($component_type) == 'geofisik kimia' || strtolower($component_type) == 'biologi') {
                        $po_bwk[] = [
                            'po_bwk' => $ronaAwal . ' akibat ' . $component,
                            'po_bwk_ba' => ''
                        ];
                    }

                    $po[] = [
                        'ka_po' => $total,
                        'ka_po_component' => $component,
                        'ka_po_plan' => '${po_' . $imp->id . '_study_plan}',
                        'ka_po_rona_awal' => $ronaAwal,
                        'ka_po_hypothetical' => $imp->is_hypothetical_significant ? 'DPH' : 'Tidak DPH',
                        'ka_po_study_location' => str_replace('&', 'dan', $imp->study_location ?? ''),
                        'ka_po_study_length' => $imp->study_length_year . ' tahun ' . $imp->study_length_month . ' bulan',
                        'ka_po_potential_impact' => "$change_type $ronaAwal akibat $component",
                        'ka_po_ed_besaran_rencana' => $ed_besaran_rencana,
                        'ka_po_ed_kondisi_rona' => $ed_kondisi_rona,
                        'ka_po_ed_pengaruh_rencana' => $ed_pengaruh_rencana,
                        'ka_po_ed_intensitas_perhatian' => $ed_intensitas_perhatian,
                        'ka_po_ed_kesimpulan' => $ed_kesimpulan,
                        'ka_po_env_impact' => "$change_type $ronaAwal"
                    ];
                    $initial_study_plan_replace[] = ['replace' => '${po_' . $imp->id . '_study_plan}' , 'data' => $initial_study_plan];

                    // MATRIKS DPH
                    $dph_po[] = [
                        'dph_po' => $total,
                        'dph_po_component' => $component,
                        'dph_po_unit' => str_replace('&', 'dan', $imp->projectComponent->measurement ?? ''),
                        'dph_po_plan' => '${dph_po_' . $imp->id . '_study_plan}',
                        'dph_po_rona_awal' => $ronaAwal,
                        'dph_po_hypothetical' => $imp->is_hypothetical_significant ? 'DPH' : 'Tidak DPH',
                        'dph_po_study_location' => str_replace('&', 'dan', $imp->study_location ?? ''),
                        'dph_po_study_length' => $imp->study_length_year . ' tahun ' . $imp->study_length_month . ' bulan',
                        'dph_po_potential_impact' => "$change_type $ronaAwal akibat $component",
                        'dph_po_ed_besaran_rencana' => $ed_besaran_rencana,
                        'dph_po_ed_kondisi_rona' => $ed_kondisi_rona,
                        'dph_po_ed_pengaruh_rencana' => $ed_pengaruh_rencana,
                        'dph_po_ed_intensitas_perhatian' => $ed_intensitas_perhatian,
                        'dph_po_ed_kesimpulan' => $ed_kesimpulan,
                        'dph_po_env_impact' => "$change_type $ronaAwal"
                    ];
                    $initial_study_plan_replace[] = ['replace' => '${dph_po_' . $imp->id . '_study_plan}' , 'data' => $initial_study_plan];

                    // BATAS WAKTU KAJIAN
                    $bwk_po[] = [
                        'bwk_po' => $total,
                        'bwk_po_component' => $component,
                        'bwk_po_env_impact' => "$change_type $ronaAwal",
                        'bwk_po_study_length' => $imp->study_length_year . ' tahun ' . $imp->study_length_month . ' bulan',
                    ];
                }
                $stage_id = $s->id;
                $total++;
            }

            // DESKRIPSI KEGIATAN
            $com_desc = '';
            $sub_project_component_stages = SubProjectComponent::where(function($q) use($project, $s) {
                $q->whereHas('subProject', function($query) use($project) {
                    $query->where('id_project', $project->id);
                });
                $q->whereHas('component', function($query) use($s) {
                    $query->where('id_project_stage', $s->id);
                });
            })->first();

            if($sub_project_component_stages) {
                if($sub_project_component_stages->description_common) {
                    $com_desc = $sub_project_component_stages->description_common . '<br/>' . $sub_project_component_stages->description_specific;
                } else {
                    $com_desc = $sub_project_component_stages->description_specific;
                }

                $component_stages = str_replace('&', 'dan', $sub_project_component_stages->id_component  ? $sub_project_component_stages->component->name : $sub_project_component_stages->name);

                 // COMPONENT

                 if($s->name == 'Pra Konstruksi') {
                     if (!in_array($component_stages, $com_pk_name)) {
                         $com_pk_name[] = $component_stages;
                         $com_pk[] = [
                             'com_pk_name' => '2.1.' . count($com_pk_name) . ' ' . $component_stages,
                             'com_pk_desc' => '${com_pk_desc_' . $s->id . '}',
                         ];
                         $com_replace[] = [
                             'data' => $this->renderHtmlTable($com_desc),
                             'replace' => '${com_pk_desc_' . $s->id . '}',
                         ];
                     }
                 } else if($s->name == 'Konstruski') {
                     if (!in_array($component_stages, $com_k_name)) {
                         $com_k_name[] = $component_stages;
                         $com_k[] = [
                             'com_k_name' => '2.2.' . count($com_k_name) . ' ' . $component_stages,
                             'com_k_desc' => '${com_k_desc_' . $s->id . '}',
                         ];
                        $com_replace[] = [
                            'data' => $this->renderHtmlTable($com_desc),
                            'replace' => '${com_k_desc_' . $s->id . '}',
                        ];
                     }
                 } else if($s->name == 'Operasi') {
                    if (!in_array($component_stages, $com_o_name)) {
                        $com_o_name[] = $component_stages;
                        $com_o[] = [
                            'com_o_name' => '2.3.' . count($com_o_name) . ' ' . $component_stages,
                            'com_o_desc' => '${com_o_desc_' . $s->id . '}',
                        ];
                        $com_replace[] = [
                            'data' => $this->renderHtmlTable($com_desc),
                            'replace' => '${com_o_desc_' . $s->id . '}',
                        ];
                    }
                 } else if($s->name == 'Pasca Operasi') {
                    if (!in_array($component_stages, $com_po_name)) {
                        $com_po_name[] = $component_stages;
                        $com_po[] = [
                            'com_po_name' => '2.4.' . count($com_po_name) . ' ' . $component_stages,
                            'com_po_desc' => '${com_po_desc_' . $s->id . '}',
                        ];
                        $com_replace[] = [
                            'data' => $this->renderHtmlTable($com_desc),
                            'replace' => '${com_po_desc_' . $s->id . '}',
                        ];
                    }
                 }
            }
        }

        // ======= KOMPONEN LINGKUNGAN HIDUP ====== //
        $sub_project_rona_awal = SubProjectRonaAwal::whereHas('subProjectComponent', function($q) use($project) {
            $q->whereHas('subProject', function($query) use($project) {
                $query->where('id_project', $project->id);
            });
        })->get();

        $spra_desc_replace = [];

        foreach($sub_project_rona_awal as $spra) {
            $component_type_sub_project = $this->getComponentType($spra);

            $komponen_desc = '';
            if($spra->description_common) {
                $komponen_desc = $spra->description_common . '<br/>' . $spra->description_specific;
            } else {
                $komponen_desc = $spra->description_specific;
            }

            if(strtolower($component_type_sub_project) == 'geofisik kimia') {
                $gfk_no = count($gfk_rona_block) + 1;
                $gfk_rona_block[] = [
                    'gfk_rona_name' => '3.1.1.' . $gfk_no . ' ' . $ronaAwal,
                    'gfk_rona_desc' => '${gfk_rona_des_' . $spra->id . '}',
                ];
                $spra_desc_replace[] = [
                    'data' => $this->renderHtmlTable($komponen_desc),
                    'replace' => '${gfk_rona_des_' . $spra->id . '}',
                ];
            } else if(strtolower($component_type_sub_project) == 'biologi') {
                $b_no = count($b_rona_block) + 1;
                $b_rona_block[] = [
                    'b_rona_name' => '3.1.2.' . $b_no . ' ' . $ronaAwal,
                    'b_rona_desc' => '${b_rona_des_' . $spra->id . '}',
                ];
                $spra_desc_replace[] = [
                    'data' => $this->renderHtmlTable($komponen_desc),
                    'replace' => '${b_rona_des_' . $spra->id . '}',
                ];
            } else if(strtolower($component_type_sub_project) == 'sosial, ekonomi, dan budaya') {
                $seb_no = count($seb_rona_block) + 1;
                $seb_rona_block[] = [
                    'seb_rona_name' => '3.1.3.' . $seb_no . ' ' . $ronaAwal,
                    'seb_rona_desc' => '${seb_rona_des_' . $spra->id . '}',
                ];
                $spra_desc_replace[] = [
                    'data' => $this->renderHtmlTable($komponen_desc),
                    'replace' => '${seb_rona_des_' . $spra->id . '}',
                ];
            } else if(strtolower($component_type_sub_project) == 'kesehatan masyarakat') {
                $kk_no = count($kk_rona_block) + 1;
                $kk_rona_block[] = [
                    'kk_rona_name' => '3.1.4.' . $kk_no . ' ' . $ronaAwal,
                    'kk_rona_desc' => '${kk_rona_des_' . $spra->id . '}',
                ];
                $spra_desc_replace[] = [
                    'data' => $this->renderHtmlTable($komponen_desc),
                    'replace' => '${kk_rona_des_' . $spra->id . '}',
                ];
            }
        }

        // ============ PROJECT ADDRESS ============ //
        $project_address = '';
        $project_district = '';
        $project_province = '';
        if ($project->address) {
            if ($project->address->first()) {
                $project_address = $project->address->first()->address;
                $project_district = $project->address->first()->district;
                $project_province = $project->address->first()->prov;
            }
        }

        // ========= DESKRIPSI RENCANA USAHA DAN/ATAU KEGIATAN ======== //
        $deskripsi_rencana = [];
        $desk_ren_no = 'A';
        $sub_projects = SubProject::where([['id_project', $id_project],['type', 'utama']])->get();
        foreach($sub_projects as $sp) {
            $sub_project_component = SubProjectComponent::where('id_sub_project', $sp->id)->get();
            $last_ren_no = null;
            foreach($sub_project_component as $spc) {
                $desk_ren_pen = '';
                if($spc->name) {
                    $desk_ren_pen = str_replace('&', 'dan', $spc->name ?? '');
                } else if($spc->component) {
                    $desk_ren_pen =  str_replace('&', 'dan', $spc->component->name ?? '');
                }

                if($last_ren_no == $desk_ren_no) {
                    $ren_no = $desk_ren_no . '<w:vMerge w:val="continue"/>';
                    $ren_ru =  str_replace('&', 'dan', $sp->name ?? '') . '<w:vMerge w:val="continue"/>';
                } else {
                    $ren_no = $desk_ren_no . '<w:vMerge w:val="restart"/>';
                    $ren_ru =  str_replace('&', 'dan', $sp->name ?? '') . '<w:vMerge w:val="restart"/>';
                }

                $deskripsi_rencana[] = [
                    'deskripsi_rencana' => $ren_no,
                    'deskripsi_rencana_ru' => $ren_ru,
                    'deskripsi_rencana_pendukung' => $desk_ren_pen,
                    'deskripsi_rencana_besaran' => $spc->unit,
                    'deskripsi_rencana_lokasi' => ''
                ];
                $deskripsi_rencana_replace[] = [
                    'data' => $this->renderHtmlTable($spc->description_common, 1200),
                    'replace' => '${drk_' . $sp->id . '_' . $spc->id . '}'
                ];
                $last_ren_no = $desk_ren_no;
            }
            $desk_ren_no++;
        }

        // ======= RONA AWAL ============
        // $fisika_kima = [];
        // $biologi = [];
        // $sosekbud = [];
        // $kesmas = [];

        // $component_type = ComponentType::all();
        // foreach($component_type as $ct) {
        //     if($ct->name == 'Biologi') {
        //         $rona_awal = RonaAwal::where('id_component_type', $ct->id)->get();
        //         foreach($rona_awal as $ra) {
        //             $biologi[] = [
        //                 'rona_biologi_name' => $ra->name
        //             ];
        //         }
        //         $sp_rona = SubProject::where('id_project', $id_project)->get();
        //         foreach($sp_rona as $sr) {
        //             $spr_component = SubProjectComponent::where('id_sub_project', $sr->id)->get();
        //             foreach($spr_component as $sprc) {
        //                 $spr_rona = SubProjectRonaAwal::where([['id_sub_project_component', $sprc->id],['id_component_type', $sr->id]])->get();
        //                 foreach($spr_rona as $sprr) {
        //                     $biologi[] = [
        //                         'rona_biologi_name' => $sprr->name
        //                     ];
        //                 }
        //             }
        //         }
        //     }
        // }

        // ======== KONSULTASI PUBLIK ======== //
        $konsul_publik_date = '';
        $konsul_publik_lokasi = '';
        $konsul_publik_peserta = '';
        $konsul_publik_total = '';
        $positive_feedback_summary = '';
        $negative_feedback_summary = '';
        $konsultasi_publik = PublicConsultation::where('project_id', $id_project)->first();
        if($konsultasi_publik) {
            $konsul_publik_date = $konsultasi_publik->event_date ? Carbon::createFromFormat('Y-m-d',  $konsultasi_publik->event_date)->isoFormat('D MMMM Y')  : '' ;
            $konsul_publik_lokasi = $konsultasi_publik->location;
            $konsul_publik_peserta = '';
            $konsul_publik_total = $konsultasi_publik->participant;
            $positive_feedback_summary = $konsultasi_publik->positive_feedback_summary ?? '';
            $negative_feedback_summary = $konsultasi_publik->negative_feedback_summary ?? '';
        }

        $posFeedTable = $this->renderHtmlTable($positive_feedback_summary, null, 'Arial', '15');
        $negFeedTable = $this->renderHtmlTable($negative_feedback_summary, null, 'Arial', '15');

        // ======= EVALUASI HOLISTIK ====== //
        $holistic_evaluations = '';
        $hol_eval = HolisticEvaluation::where('id_project', $id_project)->first();
        if($hol_eval) {
            $holistic_evaluations = $hol_eval->description;
        }
        $holEvalTable = $this->renderHtmlTable($holistic_evaluations, null, 'Arial', '15');

        $location_description = $project->location_desc ? $project->location_desc : '';
        $locDescTable = $this->renderHtmlTable($location_description, null, 'Arial', '15');

        $templateProcessor = new TemplateProcessor(storage_path('app/public/template/template_andal.docx'));

        $templateProcessor->setValue('pemrakarsa', $project->initiator->name);
        $templateProcessor->setValue('project_title_s', strtolower($project->project_title));
        $templateProcessor->setValue('project_title', ucwords(strtolower($project->project_title)));
        $templateProcessor->setComplexBlock('project_location_desc',  $locDescTable);
        $templateProcessor->setValue('district', ucwords(strtolower($project_district)));
        $templateProcessor->setValue('province', ucwords(strtolower($project_province)));
        $templateProcessor->setValue('pemrakarsa_pic', $project->initiator->pic);
        $templateProcessor->setValue('pemrakarsa_position', $project->initiator->pic_role);
        $templateProcessor->setValue('address', ucwords(strtolower($project_address)));
        $templateProcessor->setValue('pemrakarsa_address', ucwords(strtolower($project->initiator->address)));
        $templateProcessor->setValue('pemrakarsa_phone', ucwords(strtolower($project->initiator->phone)));
        $templateProcessor->setValue('date_small', Carbon::now()->isoFormat('MMMM Y'));
        $templateProcessor->setValue('year', '' . date('Y') . '');
        $templateProcessor->cloneBlock('sub_project_scale_block', count($sub_project_scale_block), true, false, $sub_project_scale_block);
        $templateProcessor->setValue('lpjp_faksimili', $lpjp['faksimili']);
        $templateProcessor->setValue('lpjp_position', $lpjp['position']);
        $templateProcessor->setValue('penyusun', $penyusun);
        $templateProcessor->setValue('penyusun_name', $penyusun_name);
        $templateProcessor->setValue('penyusun_type', $penyusun_type);
        $templateProcessor->setValue('penyusun_no_reg', $penyusun_no_reg);
        $templateProcessor->setValue('penyusun_date_start', $penyusun_date_start);
        $templateProcessor->setValue('penyusun_date_end', $penyusun_date_end);
        $templateProcessor->setValue('penyusun_period', $penyusun_period);
        $templateProcessor->setValue('penyusun_address', $penyusun_address);
        $templateProcessor->setValue('penyusun_phone', $penyusun_phone);
        $templateProcessor->cloneBlock('lpjp_pj_block', count($lpjp_pj_block), true, false, $lpjp_pj_block);
        $templateProcessor->setValue('konsul_publik_date', $konsul_publik_date);
        $templateProcessor->setValue('konsul_publik_lokasi', $konsul_publik_lokasi);
        $templateProcessor->setValue('konsul_publik_peserta', $konsul_publik_peserta);
        $templateProcessor->setValue('konsul_publik_total', $konsul_publik_total);
        $templateProcessor->cloneRowAndSetValues('tim_penyusun', $formulator);
        $templateProcessor->cloneRowAndSetValues('tim_ahli', $experts);
        $templateProcessor->cloneRowAndSetValues('ka_pk', $pk);
        $templateProcessor->cloneRowAndSetValues('ka_k', $k);
        $templateProcessor->cloneRowAndSetValues('ka_o', $o);
        $templateProcessor->cloneRowAndSetValues('ka_po', $po);
        $templateProcessor->cloneRowAndSetValues('dp_pk', $dp_pk);
        $templateProcessor->cloneRowAndSetValues('dp_k', $dp_k);
        $templateProcessor->cloneRowAndSetValues('dp_o', $dp_o);
        $templateProcessor->cloneRowAndSetValues('dp_po', $dp_po);
        $templateProcessor->cloneRowAndSetValues('mdp_pk', $mdp_pk);
        $templateProcessor->cloneRowAndSetValues('mdp_k', $mdp_k);
        $templateProcessor->cloneRowAndSetValues('mdp_o', $mdp_o);
        $templateProcessor->cloneRowAndSetValues('mdp_po', $mdp_po);
        $templateProcessor->cloneRowAndSetValues('dph_pk', $dph_pk);
        $templateProcessor->cloneRowAndSetValues('dph_k', $dph_k);
        $templateProcessor->cloneRowAndSetValues('dph_o', $dph_o);
        $templateProcessor->cloneRowAndSetValues('dph_po', $dph_po);
        $templateProcessor->cloneRowAndSetValues('mdph_pk', $mdph_pk);
        $templateProcessor->cloneRowAndSetValues('mdph_k', $mdph_k);
        $templateProcessor->cloneRowAndSetValues('mdph_o', $mdph_o);
        $templateProcessor->cloneRowAndSetValues('mdph_po', $mdph_po);
        $templateProcessor->cloneRowAndSetValues('bwk_pk', $bwk_pk);
        $templateProcessor->cloneRowAndSetValues('bwk_k', $bwk_k);
        $templateProcessor->cloneRowAndSetValues('bwk_o', $bwk_o);
        $templateProcessor->cloneRowAndSetValues('bwk_po', $bwk_po);
        $templateProcessor->cloneRowAndSetValues('ru_pk', $ru_pk);
        $templateProcessor->cloneRowAndSetValues('ru_k', $ru_k);
        $templateProcessor->cloneRowAndSetValues('ru_o', $ru_o);
        $templateProcessor->cloneRowAndSetValues('ru_po', $ru_po);
        $templateProcessor->cloneRowAndSetValues('deskripsi_rencana', $deskripsi_rencana);
        $templateProcessor->cloneRowAndSetValues('pk_bwk', $pk_bwk);
        $templateProcessor->cloneRowAndSetValues('k_bwk', $k_bwk);
        $templateProcessor->cloneRowAndSetValues('o_bwk', $o_bwk);
        $templateProcessor->cloneRowAndSetValues('po_bwk', $po_bwk);
        $templateProcessor->cloneBlock('com_pk_block', count($com_pk), true, false, $com_pk);
        $templateProcessor->cloneBlock('com_k_block', count($com_k), true, false, $com_k);
        $templateProcessor->cloneBlock('com_o_block', count($com_o), true, false, $com_o);
        $templateProcessor->cloneBlock('com_po_block', count($com_po), true, false, $com_po);
        $templateProcessor->cloneBlock('gfk_rona_block', count($gfk_rona_block), true, false, $gfk_rona_block);
        $templateProcessor->cloneBlock('b_rona_block', count($b_rona_block), true, false, $b_rona_block);
        $templateProcessor->cloneBlock('seb_rona_block', count($seb_rona_block), true, false, $seb_rona_block);
        $templateProcessor->cloneBlock('kk_rona_block', count($kk_rona_block), true, false, $kk_rona_block);
        $templateProcessor->setComplexBlock('positive_feedback_summary',  $posFeedTable);
        $templateProcessor->setComplexBlock('negative_feedback_summary',  $negFeedTable);
        $templateProcessor->setComplexBlock('holistic_evaluation',  $holEvalTable);

        // PENGELOLAAN LINGKUNGAN YANG DIRENCANAKAN
        if(count($initial_study_plan_replace) > 0) {
            for($ispr = 0; $ispr < count($initial_study_plan_replace); $ispr++) {
                $templateProcessor->setComplexBlock($initial_study_plan_replace[$ispr]['replace'],  $initial_study_plan_replace[$ispr]['data']);
            }
        }

        // DESKRIPSI KEGIATAN
        if(count($com_replace) > 0) {
            for($cri = 0; $cri < count($com_replace); $cri++) {
                $templateProcessor->setComplexBlock($com_replace[$cri]['replace'],  $com_replace[$cri]['data']);
            }
        }

        // KOMPONEN LINGKUNGAN HIDUP
        if(count($spra_desc_replace) > 0) {
            for($spri = 0; $spri < count($spra_desc_replace); $spri++) {
                $templateProcessor->setComplexBlock($spra_desc_replace[$spri]['replace'],  $spra_desc_replace[$spri]['data']);
            }
        }

        // DESKRIPSI RENCANA USAHA DAN/ATAU KEGIATAN
        if(count($deskripsi_rencana_replace) > 0) {
            for($dski = 0; $dski < count($deskripsi_rencana_replace); $dski++) {
                $templateProcessor->setComplexBlock($deskripsi_rencana_replace[$dski]['replace'],  $deskripsi_rencana_replace [$dski]['data']);
            }
        }

        // KEGIATAN LAIN DI SEKITAR
        $kegiatan_lain_sekitar = ProjectKegiatanLainSekitar::where([['project_id', $project->id],['is_andal', true]])->get();
        $kls_replace = [];
        $kls_total = 1;
        foreach($kegiatan_lain_sekitar as $kls) {
            $kls_table = new Table();
            $kls_table->addRow();
            $kls_cell = $kls_table->addCell();
            $kls_content = '';
            if($kls->description) {
                $kls_content = str_replace('<p>', '<p style="font-family: Calibri; font-size: 15px;">', $kls->description);
            }
            Html::addHtml($kls_cell, $this->replaceHtmlList($kls_content));

            $kls_block[] = [
                'kls_name' => '3.2.' . $kls_total . ' ' . $kls->kegiatanLainSekitar->name,
                'kls_desc' => '${kls_desc_' . $kls->id . '}',
                'kls_unit' => $kls->measurement,
            ];
            $kls_replace[] = [
                'replace' => '${kls_desc_' . $kls->id . '}',
                'desc' => $kls_table,
            ];
            $kls_total++;
        }

        $templateProcessor->cloneBlock('kls_block', count($kls_block), true, false, $kls_block);

        if(count($kls_replace) > 0) {
            for($klsi = 0; $klsi < count($kls_replace); $klsi++) {
                $templateProcessor->setComplexBlock($kls_replace[$klsi]['replace'], $kls_replace[$klsi]['desc']);
            }
        }

        // PRAKIRAAN DAMPAK PENTING
        $pdp = [];
        for($i = 0; $i < count($dpg_pk_block); $i++) {
            $pdp[] = [
                'dpg_pk_component_no' => '6.1.' . $i + 1 . '. ' . $dpg_pk_block[$i]['dpg_pk_component'],
                'dpg_pk_component' => $dpg_pk_block[$i]['dpg_pk_component'],
                'dpg_pk_imp_block' => $dpg_pk_block[$i]['dpg_pk_imp_block'],
                '/dpg_pk_imp_block' => $dpg_pk_block[$i]['/dpg_pk_imp_block'],
            ];
        }
        $templateProcessor->cloneBlock('dpg_pk_block', count($pdp), true, false, $pdp);

        $pdp = [];
        for($i = 0; $i < count($dpg_k_block); $i++) {
            $pdp[] = [
                'dpg_k_component_no' => '6.2.' . $i + 1 . '. ' . $dpg_k_block[$i]['dpg_k_component'],
                'dpg_k_component' => $dpg_k_block[$i]['dpg_k_component'],
                'dpg_k_imp_block' => $dpg_k_block[$i]['dpg_k_imp_block'],
                '/dpg_k_imp_block' => $dpg_k_block[$i]['/dpg_k_imp_block'],
            ];
        }
        $templateProcessor->cloneBlock('dpg_k_block', count($pdp), true, false, $pdp);

        $pdp = [];
        for($i = 0; $i < count($dpg_o_block); $i++) {
            $pdp[] = [
                'dpg_o_component_no' => '6.3.' . $i + 1 . '. ' . $dpg_o_block[$i]['dpg_o_component'],
                'dpg_o_component' => $dpg_o_block[$i]['dpg_o_component'],
                'dpg_o_imp_block' => $dpg_o_block[$i]['dpg_o_imp_block'],
                '/dpg_o_imp_block' => $dpg_o_block[$i]['/dpg_o_imp_block'],
            ];
        }
        $templateProcessor->cloneBlock('dpg_o_block', count($pdp), true, false, $pdp);

        $pdp = [];
        for($i = 0; $i < count($dpg_po_block); $i++) {
            $pdp[] = [
                'dpg_po_component_no' => '6.3.' . $i + 1 . '. ' . $dpg_po_block[$i]['dpg_po_component'],
                'dpg_po_component' => $dpg_po_block[$i]['dpg_po_component'],
                'dpg_po_imp_block' => $dpg_po_block[$i]['dpg_po_imp_block'],
                '/dpg_po_imp_block' => $dpg_po_block[$i]['/dpg_po_imp_block'],
            ];
        }
        $templateProcessor->cloneBlock('dpg_po_block', count($pdp), true, false, $pdp);

        // DAMPAK PADA PRAKIRAAN DAMPAK PENTING
        if(count($dpg_pk_block) > 0) {
            for($i = 0; $i < count($dpg_pk_block); $i++) {
                $dampak = [];
                for($a = 0; $a < count($dpg_pk_block[$i]['dampak']); $a++) {
                    $dampak[$a] = [
                        'dpg_pk_impact_no' => '6.1.' . $i + 1 . '.' . $a + 1 . '. ' . $dpg_pk_block[$i]['dampak'][$a],
                        'dpg_pk_impact' => $dpg_pk_block[$i]['dampak'][$a],
                        'dpg_pk_impact_small' => strtolower($dpg_pk_block[$i]['dampak'][$a]),
                        'dpg_pk_studies' => $dpg_pk_block[$i]['table_with_no_plan'][$a]['studies'],
                        'dpg_pk_no_plan' => $dpg_pk_block[$i]['table_with_no_plan'][$a]['no_plan'],
                        'dpg_pk_with_plan' => $dpg_pk_block[$i]['table_with_no_plan'][$a]['with_plan'],
                        'dpg_pk_size_differ' => $dpg_pk_block[$i]['table_with_no_plan'][$a]['size_differ'],
                        'dpg_pk_imp_result' => $dpg_pk_block[$i]['impact_result'][$a],
                    ];

                    $status = 'tidak penting';

                    for($o = 1; $o < count($dpg_pk_block[$i]['important_trait'][$a]) + 1; $o++) {
                        $dampak[$a]['dpg_pk_it_' . $o] = $dpg_pk_block[$i]['important_trait'][$a][$o - 1]['nilai'];
                        $dampak[$a]['dpg_pk_itk_' . $o] = $dpg_pk_block[$i]['important_trait'][$a][$o - 1]['keterangan'];

                        if($status == 'tidak penting') {
                            if($dampak[$a]['dpg_pk_it_' . $o] == '+P' || $dampak[$a]['dpg_pk_it_' . $o] == '-P') {
                                $status = 'penting';
                            }
                        }
                    }

                    $dampak[$a]['dpg_pk_imp_status'] = $status;
                }
                $no = $i + 1;
                $templateProcessor->cloneBlock('dpg_pk_imp_block_' . $no, count($dampak), true, false, $dampak);

                for($a = 0; $a < count($dpg_pk_block[$i]['dampak']); $a++) {
                    if($dpg_pk_block[$i]['table_with_no_plan'][$a]['studies'] !== '') {
                        $templateProcessor->setComplexBlock($dpg_pk_block[$i]['table_with_no_plan'][$a]['studies'], $dpg_pk_block[$i]['table_with_no_plan_data'][$a]['studies']);
                        $templateProcessor->setComplexBlock($dpg_pk_block[$i]['table_with_no_plan'][$a]['no_plan'], $dpg_pk_block[$i]['table_with_no_plan_data'][$a]['no_plan']);
                        $templateProcessor->setComplexBlock($dpg_pk_block[$i]['table_with_no_plan'][$a]['with_plan'], $dpg_pk_block[$i]['table_with_no_plan_data'][$a]['with_plan']);
                        $templateProcessor->setComplexBlock($dpg_pk_block[$i]['table_with_no_plan'][$a]['size_differ'], $dpg_pk_block[$i]['table_with_no_plan_data'][$a]['size_differ']);

                        for($o = 1; $o < count($dpg_pk_block[$i]['important_trait'][$a]) + 1; $o++) {
                            $templateProcessor->setComplexBlock($dpg_pk_block[$i]['important_trait'][$a][$o - 1]['keterangan'], $dpg_pk_block[$i]['important_trait_data'][$a][$o - 1]['keterangan']);
                        }
                    }
                }
            }
        }

        if(count($dpg_k_block) > 0) {
            for($i = 0; $i < count($dpg_k_block); $i++) {
                $dampak = [];
                for($a = 0; $a < count($dpg_k_block[$i]['dampak']); $a++) {
                    $dampak[$a] = [
                        'dpg_k_impact_no' => '6.2.' . $i + 1 . '.' . $a + 1 . '. ' . $dpg_k_block[$i]['dampak'][$a],
                        'dpg_k_impact' => $dpg_k_block[$i]['dampak'][$a],
                        'dpg_k_impact_small' => strtolower($dpg_k_block[$i]['dampak'][$a]),
                        'dpg_k_studies' => $dpg_k_block[$i]['table_with_no_plan'][$a]['studies'],
                        'dpg_k_no_plan' => $dpg_k_block[$i]['table_with_no_plan'][$a]['no_plan'],
                        'dpg_k_with_plan' => $dpg_k_block[$i]['table_with_no_plan'][$a]['with_plan'],
                        'dpg_k_size_differ' => $dpg_k_block[$i]['table_with_no_plan'][$a]['size_differ'],
                        'dpg_k_imp_result' => $dpg_k_block[$i]['impact_result'][$a],
                    ];

                    $status = 'tidak penting';

                    for($o = 1; $o < count($dpg_k_block[$i]['important_trait'][$a]) + 1; $o++) {
                        $dampak[$a]['dpg_k_it_' . $o] = $dpg_k_block[$i]['important_trait'][$a][$o - 1]['nilai'];
                        $dampak[$a]['dpg_k_itk_' . $o] = $dpg_k_block[$i]['important_trait'][$a][$o - 1]['keterangan'];

                        if($status == 'tidak penting') {
                            if($dampak[$a]['dpg_k_it_' . $o] == '+P' || $dampak[$a]['dpg_k_it_' . $o] == '-P') {
                                $status = 'penting';
                            }
                        }
                    }

                    $dampak[$a]['dpg_k_imp_status'] = $status;
                }
                $no = $i + 1;
                $templateProcessor->cloneBlock('dpg_k_imp_block_' . $no, count($dampak), true, false, $dampak);

                for($a = 0; $a < count($dpg_k_block[$i]['dampak']); $a++) {
                    if($dpg_k_block[$i]['table_with_no_plan'][$a]['studies'] !== '') {
                        $templateProcessor->setComplexBlock($dpg_k_block[$i]['table_with_no_plan'][$a]['studies'], $dpg_k_block[$i]['table_with_no_plan_data'][$a]['studies']);
                        $templateProcessor->setComplexBlock($dpg_k_block[$i]['table_with_no_plan'][$a]['no_plan'], $dpg_k_block[$i]['table_with_no_plan_data'][$a]['no_plan']);
                        $templateProcessor->setComplexBlock($dpg_k_block[$i]['table_with_no_plan'][$a]['with_plan'], $dpg_k_block[$i]['table_with_no_plan_data'][$a]['with_plan']);
                        $templateProcessor->setComplexBlock($dpg_k_block[$i]['table_with_no_plan'][$a]['size_differ'], $dpg_k_block[$i]['table_with_no_plan_data'][$a]['size_differ']);

                        for($o = 1; $o < count($dpg_k_block[$i]['important_trait'][$a]) + 1; $o++) {
                            $templateProcessor->setComplexBlock($dpg_k_block[$i]['important_trait'][$a][$o - 1]['keterangan'], $dpg_k_block[$i]['important_trait_data'][$a][$o - 1]['keterangan']);
                        }
                    }
                }
            }
        }

        if(count($dpg_o_block) > 0) {
            for($i = 0; $i < count($dpg_o_block); $i++) {
                $dampak = [];
                for($a = 0; $a < count($dpg_o_block[$i]['dampak']); $a++) {
                    $dampak[$a] = [
                        'dpg_o_impact_no' => '6.3.' . $i + 1 . '.' . $a + 1 . '. ' . $dpg_o_block[$i]['dampak'][$a],
                        'dpg_o_impact' => $dpg_o_block[$i]['dampak'][$a],
                        'dpg_o_impact_small' => strtolower($dpg_o_block[$i]['dampak'][$a]),
                        'dpg_o_studies' => $dpg_o_block[$i]['table_with_no_plan'][$a]['studies'],
                        'dpg_o_no_plan' => $dpg_o_block[$i]['table_with_no_plan'][$a]['no_plan'],
                        'dpg_o_with_plan' => $dpg_o_block[$i]['table_with_no_plan'][$a]['with_plan'],
                        'dpg_o_size_differ' => $dpg_o_block[$i]['table_with_no_plan'][$a]['size_differ'],
                        'dpg_o_imp_result' => $dpg_o_block[$i]['impact_result'][$a],
                    ];

                    $status = 'tidak penting';

                    for($o = 1; $o < count($dpg_o_block[$i]['important_trait'][$a]) + 1; $o++) {
                        $dampak[$a]['dpg_o_it_' . $o] = $dpg_o_block[$i]['important_trait'][$a][$o - 1]['nilai'];
                        $dampak[$a]['dpg_o_itk_' . $o] = $dpg_o_block[$i]['important_trait'][$a][$o - 1]['keterangan'];

                        if($status == 'tidak penting') {
                            if($dampak[$a]['dpg_o_it_' . $o] == '+P' || $dampak[$a]['dpg_o_it_' . $o] == '-P') {
                                $status = 'penting';
                            }
                        }
                    }

                    $dampak[$a]['dpg_o_imp_status'] = $status;
                }
                $no = $i + 1;
                $templateProcessor->cloneBlock('dpg_o_imp_block_' . $no, count($dampak), true, false, $dampak);

                for($a = 0; $a < count($dpg_o_block[$i]['dampak']); $a++) {
                    if($dpg_o_block[$i]['table_with_no_plan'][$a]['studies'] !== '') {
                        $templateProcessor->setComplexBlock($dpg_o_block[$i]['table_with_no_plan'][$a]['studies'], $dpg_o_block[$i]['table_with_no_plan_data'][$a]['studies']);
                        $templateProcessor->setComplexBlock($dpg_o_block[$i]['table_with_no_plan'][$a]['no_plan'], $dpg_o_block[$i]['table_with_no_plan_data'][$a]['no_plan']);
                        $templateProcessor->setComplexBlock($dpg_o_block[$i]['table_with_no_plan'][$a]['with_plan'], $dpg_o_block[$i]['table_with_no_plan_data'][$a]['with_plan']);
                        $templateProcessor->setComplexBlock($dpg_o_block[$i]['table_with_no_plan'][$a]['size_differ'], $dpg_o_block[$i]['table_with_no_plan_data'][$a]['size_differ']);

                        for($o = 1; $o < count($dpg_o_block[$i]['important_trait'][$a]) + 1; $o++) {
                            $templateProcessor->setComplexBlock($dpg_o_block[$i]['important_trait'][$a][$o - 1]['keterangan'], $dpg_o_block[$i]['important_trait_data'][$a][$o - 1]['keterangan']);
                        }
                    }
                }
            }
        }

        if(count($dpg_po_block) > 0) {
            for($i = 0; $i < count($dpg_po_block); $i++) {
                $dampak = [];
                for($a = 0; $a < count($dpg_po_block[$i]['dampak']); $a++) {
                    $dampak[$a] = [
                        'dpg_po_impact_no' => '6.4.' . $i + 1 . '.' . $a + 1 . '. ' . $dpg_po_block[$i]['dampak'][$a],
                        'dpg_po_impact' => $dpg_po_block[$i]['dampak'][$a],
                        'dpg_po_impact_small' => strtolower($dpg_po_block[$i]['dampak'][$a]),
                        'dpg_po_studies' => $dpg_po_block[$i]['table_with_no_plan'][$a]['studies'],
                        'dpg_po_no_plan' => $dpg_po_block[$i]['table_with_no_plan'][$a]['no_plan'],
                        'dpg_po_with_plan' => $dpg_po_block[$i]['table_with_no_plan'][$a]['with_plan'],
                        'dpg_po_size_differ' => $dpg_po_block[$i]['table_with_no_plan'][$a]['size_differ'],
                        'dpg_po_imp_result' => $dpg_po_block[$i]['impact_result'][$a],
                    ];

                    $status = 'tidak penting';

                    for($o = 1; $o < count($dpg_po_block[$i]['important_trait'][$a]) + 1; $o++) {
                        $dampak[$a]['dpg_po_it_' . $o] = $dpg_po_block[$i]['important_trait'][$a][$o - 1]['nilai'];
                        $dampak[$a]['dpg_po_itk_' . $o] = $dpg_po_block[$i]['important_trait'][$a][$o - 1]['keterangan'];

                        if($status == 'tidak penting') {
                            if($dampak[$a]['dpg_po_it_' . $o] == '+P' || $dampak[$a]['dpg_po_it_' . $o] == '-P') {
                                $status = 'penting';
                            }
                        }
                    }

                    $dampak[$a]['dpg_po_imp_status'] = $status;
                }
                $no = $i + 1;
                $templateProcessor->cloneBlock('dpg_po_imp_block_' . $no, count($dampak), true, false, $dampak);

                for($a = 0; $a < count($dpg_po_block[$i]['dampak']); $a++) {
                    if($dpg_po_block[$i]['table_with_no_plan'][$a]['studies'] !== '') {
                        $templateProcessor->setComplexBlock($dpg_po_block[$i]['table_with_no_plan'][$a]['studies'], $dpg_po_block[$i]['table_with_no_plan_data'][$a]['studies']);
                        $templateProcessor->setComplexBlock($dpg_po_block[$i]['table_with_no_plan'][$a]['no_plan'], $dpg_po_block[$i]['table_with_no_plan_data'][$a]['no_plan']);
                        $templateProcessor->setComplexBlock($dpg_po_block[$i]['table_with_no_plan'][$a]['with_plan'], $dpg_po_block[$i]['table_with_no_plan_data'][$a]['with_plan']);
                        $templateProcessor->setComplexBlock($dpg_po_block[$i]['table_with_no_plan'][$a]['size_differ'], $dpg_po_block[$i]['table_with_no_plan_data'][$a]['size_differ']);

                        for($o = 1; $o < count($dpg_po_block[$i]['important_trait'][$a]) + 1; $o++) {
                            $templateProcessor->setComplexBlock($dpg_po_block[$i]['important_trait'][$a][$o - 1]['keterangan'], $dpg_po_block[$i]['important_trait_data'][$a][$o - 1]['keterangan']);
                        }
                    }
                }
            }
        }

        // EVALUASI DAMPAK
        if(count($ed_replace) > 0) {
            for($edi = 0; $edi < count($ed_replace); $edi++) {
                $templateProcessor->setComplexBlock($ed_replace[$edi]['replace'], $ed_replace[$edi]['data']);
            }
        }

        // $templateProcessor->saveAs(Storage::disk('public')->path('workspace/' . $save_file_name));
        $tmpName = $templateProcessor->save();
        Storage::disk('public')->put('workspace/' . $save_file_name, file_get_contents($tmpName));
        unlink($tmpName);

        $document_attachment = new DocumentAttachment();
        $document_attachment->id_project = $id_project;
        $document_attachment->attachment = 'workspace/' . $save_file_name;
        $document_attachment->type = 'Dokumen Andal';
        $document_attachment->save();

        return response()->json(['message' => 'success']);
    }

    // private function htmlInTable($data)
    // {
    //     if($data) {
    //         return str_replace('&nbsp', ' ', str_replace('-enter-', '', strip_tags(str_replace('<br/>', '-enter-', str_replace('</li>', '-enter-', str_replace('</p>', '-enter-', $data))))));
    //     } else {
    //         return '';
    //     }
    // }

    private function getComponentTypeImp($imp) {
        $component_type = '';
        $com_type = ComponentType::find($imp->projectRonaAwal->rona_awal->id_component_type);
        $component_type = $com_type ? str_replace('&', 'dan', $com_type->name) : '';

        return $component_type;
    }

    private function getComponentType($sub_project_rona_awal) {
        $component_type = '';
        if($sub_project_rona_awal->id_rona_awal) {
            $com_type = ComponentType::find($sub_project_rona_awal->ronaAwal->id_component_type);
            if($com_type) {
                $component_type = str_replace('&', 'dan', $com_type->name);
            }
        } else {
            if($sub_project_rona_awal->id_component_type) {
                $com_type = ComponentType::find($sub_project_rona_awal->id_component_type);
                if($com_type) {
                    $component_type = str_replace('&', 'dan', $com_type->name);
                }
            }
        }

        return $component_type;
    }


    private function formulirKa($id_project, $type)
    {
        if (!Storage::disk('public')->exists('formulir')) {
            Storage::disk('public')->makeDirectory('formulir');
        }

        if (!Storage::disk('public')->exists('workspace')) {
            Storage::disk('public')->makeDirectory('workspace');
        }

        $ids = [4, 1, 2, 3];
        $stages = ProjectStage::select('id', 'name')->get()->sortBy(function ($model) use ($ids) {
            return array_search($model->getKey(), $ids);
        });

        $project = Project::findOrFail($id_project);

        $save_file_name = 'ka-andal-' . strtolower(str_replace('/', '-', $project->project_title)) . '.docx';
        if($type != 'andal') {
            $save_file_name = 'ka-' . $project->id . '-' . strtolower(str_replace('/', '-', $project->project_title)) . '.docx';
        }

        // === CHECK IF FORMULIR KA HAS SUBMITTED === //
        if($type != 'andal') {
            // $submit = KaReview::where([['id_project', $id_project], ['status', 'submit']])->first();
            // if($submit) {
                $document_attachment = DocumentAttachment::where([['id_project', $id_project],['type', 'Formulir KA']])->first();
                if($document_attachment) {
                    $pdf_url = $this->docxToPdf($document_attachment->attachment);
                    return [
                        'file_name' => $document_attachment->attachment,
                        'project_title' => strtolower(str_replace('/', '-', $project->project_title)),
                        'pdf_url' => $pdf_url,
                        'file_name_ka' => $save_file_name,
                    ];
                }
            // }
        }
        $fomulator_team = FormulatorTeam::where('id_project', $project->id)->first();
        $fomulator_team_member = FormulatorTeamMember::where('id_formulator_team', $fomulator_team->id)->orderBy('position', 'desc')->get();
        $penyusun = [];

        foreach($fomulator_team_member as $member) {
            if($member->formulator) {
                $penyusun[] = [
                    'name' => count($penyusun) + 1 . '. ' . $member->formulator->name,
                    'position' => $member->position
                ];
            } else if($member->expert) {
                $penyusun[] = [
                    'name' => count($penyusun) + 1 . '. ' . $member->expert->name,
                    'position' => $member->position
                ];
            }
        }

        $results = [
            'project_title' => $project->project_title,
            'pic' => $project->initiator->pic,
            'description' => $project->description,
            'location_desc' => $project->location_desc
        ];

        $publicConsultation = PublicConsultation::select('id', 'project_id', 'positive_feedback_summary', 'negative_feedback_summary')
            ->where('project_id', $id_project)->get();

        $results['positive'] = [];
        $results['negative'] = [];
        $positive = [];
        $negative = [];

        $positive_number = 0;
        $negative_number = 0;
        foreach ($publicConsultation as $p) {
            $results['positive'][] = ['val' => $p->positive_feedback_summary ?? ''];
            $results['negative'][] = ['val' => $p->negative_feedback_summary ?? ''];

            if($p->positive_feedback_summary) {
                $positive[] = ['val' => "$" . "{" . 'positive-' . $positive_number . "}"];
                $positive_number++;
            }

            if($p->negative_feedback_summary) {
                $negative[] = ['val' => "$" . "{" . 'negative-' . $negative_number . "}"];
                $negative_number++;
            }
        }

        $im = null;

        if($type == 'andal') {
            $im = ImpactIdentificationClone::select('id', 'id_project', 'id_project_component', 'id_change_type', 'id_project_rona_awal', 'initial_study_plan', 'is_hypothetical_significant', 'study_location', 'study_length_year', 'study_length_month')
                ->where('id_project', $id_project)->with('potentialImpactEvaluation.pieParam')->get();
        } else {
            $im = ImpactIdentification::select('id', 'id_project', 'id_project_component', 'id_change_type', 'id_project_rona_awal', 'initial_study_plan', 'is_hypothetical_significant', 'study_location', 'study_length_year', 'study_length_month')
                ->where('id_project', $id_project)->with('potentialImpactEvaluation.pieParam')->get();
        }

        $total_ms = 0;
        $pra_konstruksi = [];
        $konstruksi = [];
        $operasi = [];
        $pasca_operasi = [];
        $metode_studi = [];
        $html_content = [];
        $metode_replace = [];
        foreach ($stages as $s) {
            $total = 0;
            $results[str_replace(' ', '_', strtolower($s->name))] = [];
            foreach ($im as $pA) {
                $ronaAwal = '';
                $component = '';

                $data = $this->getComponentRonaAwal($pA, $s->id, $type);

                if ($data['component'] && $data['ronaAwal']) {
                    $ronaAwal = $data['ronaAwal'];
                    $component = $data['component'];
                } else {
                    continue;
                }

                $changeType = $pA->id_change_type ? $pA->changeType->name : '';

                // ======= POTENTIAL IMPACT EVALUATIONS ======= //
                $ed_besaran_rencana_title = '';
                $ed_besaran_rencana = '';
                $ed_kondisi_rona_title = '';
                $ed_kondisi_rona = '';
                $ed_pengaruh_rencana_title = '';
                $ed_pengaruh_rencana = '';
                $ed_intensitas_perhatian_title = '';
                $ed_intensitas_perhatian = '';
                $ed_kesimpulan_title = '';
                $ed_kesimpulan = '';

                if ($pA->potentialImpactEvaluation) {
                    foreach ($pA->potentialImpactEvaluation as $po) {
                        if ($po->id_pie_param == 1) {
                            $ed_besaran_rencana_title = $po->pieParam->name;
                            $ed_besaran_rencana = $po->text;
                        } else if ($po->id_pie_param == 2) {
                            $ed_kondisi_rona_title = $po->pieParam->name;
                            $ed_kondisi_rona = $po->text;
                        } else if ($po->id_pie_param == 3) {
                            $ed_pengaruh_rencana_title = $po->pieParam->name;
                            $ed_pengaruh_rencana = $po->text;
                        } else if ($po->id_pie_param == 4) {
                            $ed_intensitas_perhatian_title = $po->pieParam->name;
                            $ed_intensitas_perhatian = $po->text;
                        } else if ($po->id_pie_param == 5) {
                            $ed_kesimpulan_title = $po->pieParam->name;
                            $ed_kesimpulan = $po->text;
                        }
                    }
                }

                if($s->name == 'Pra Konstruksi') {
                    $pra_konstruksi[] = $this->getFormulirIm(
                        $s,
                        $total,
                        $changeType,
                        $ronaAwal,
                        $component,
                        $pA,
                        $ed_besaran_rencana_title,
                        $ed_kondisi_rona_title,
                        $ed_pengaruh_rencana_title,
                        $ed_intensitas_perhatian_title,
                        $ed_kesimpulan_title);
                } else if ($s->name == 'Konstruksi') {
                    $konstruksi[] = $this->getFormulirIm(
                        $s,
                        $total,
                        $changeType,
                        $ronaAwal,
                        $component,
                        $pA,
                        $ed_besaran_rencana_title,
                        $ed_kondisi_rona_title,
                        $ed_pengaruh_rencana_title,
                        $ed_intensitas_perhatian_title,
                        $ed_kesimpulan_title);
                } else if ($s->name == 'Operasi') {
                    $operasi[] = $this->getFormulirIm(
                        $s,
                        $total,
                        $changeType,
                        $ronaAwal,
                        $component,
                        $pA,
                        $ed_besaran_rencana_title,
                        $ed_kondisi_rona_title,
                        $ed_pengaruh_rencana_title,
                        $ed_intensitas_perhatian_title,
                        $ed_kesimpulan_title);
                } else {
                    $pasca_operasi[] = $this->getFormulirIm(
                        $s,
                        $total,
                        $changeType,
                        $ronaAwal,
                        $component,
                        $pA,
                        $ed_besaran_rencana_title,
                        $ed_kondisi_rona_title,
                        $ed_pengaruh_rencana_title,
                        $ed_intensitas_perhatian_title,
                        $ed_kesimpulan_title);
                }

                if ($pA->is_hypothetical_significant && $pA->impactStudy) {
                    $metode_studi[] = [
                        'metode_studi' => $total_ms + 1,
                        'potential_impact_evaluation' => "$changeType $ronaAwal akibat $component",
                        'required_information' => '${rims_' . $pA->id . '_' . $pA->impactStudy->id . '}',
                        'data_gathering_method' => '${dgmms_' . $pA->id . '_' . $pA->impactStudy->id . '}',
                        'analysis_method' =>  '${amms_' . $pA->id . '_' . $pA->impactStudy->id . '}',
                        'forecast_method' => '${fmms_' . $pA->id . '_' . $pA->impactStudy->id . '}',
                        'evaluation_method' => '${emms_' . $pA->id . '_' . $pA->impactStudy->id . '}',
                    ];
                    $metode_replace[] = [
                        'replace' => '${rims_' . $pA->id . '_' . $pA->impactStudy->id . '}',
                        'data' => $this->renderHtmlTable($pA->impactStudy->required_information, 2000),
                    ];
                    $metode_replace[] = [
                        'replace' => '${dgmms_' . $pA->id . '_' . $pA->impactStudy->id . '}',
                        'data' => $this->renderHtmlTable($pA->impactStudy->data_gathering_method, 3500),
                    ];
                    $metode_replace[] = [
                        'replace' => '${amms_' . $pA->id . '_' . $pA->impactStudy->id . '}',
                        'data' => $this->renderHtmlTable($pA->impactStudy->analysis_method, 3500),
                    ];
                    $metode_replace[] = [
                        'replace' => '${fmms_' . $pA->id . '_' . $pA->impactStudy->id . '}',
                        'data' => $this->renderHtmlTable($pA->impactStudy->forecast_method, 3500),
                    ];
                    $metode_replace[] = [
                        'replace' => '${emms_' . $pA->id . '_' . $pA->impactStudy->id . '}',
                        'data' => $this->renderHtmlTable($pA->impactStudy->evaluation_method, 3500),
                    ];
                    $total_ms++;
                }


                // === HTML CONTENT === //
                $html_content[] = $this->renderHtml('rencana', $s->id, $pA->id, 6800, $pA->initial_study_plan);
                $html_content[] = $this->renderHtml('ed_besaran_rencana', $s->id, $pA->id, 6800, $ed_besaran_rencana);
                $html_content[] = $this->renderHtml('ed_kondisi_rona', $s->id, $pA->id, 6800, $ed_kondisi_rona);
                $html_content[] = $this->renderHtml('ed_pengaruh_rencana', $s->id, $pA->id, 6800, $ed_pengaruh_rencana);
                $html_content[] = $this->renderHtml('ed_intensitas_perhatian', $s->id, $pA->id, 6800, $ed_intensitas_perhatian);
                $html_content[] = $this->renderHtml('ed_kesimpulan', $s->id, $pA->id, 6800, $ed_kesimpulan);

                $total++;
            }
        }

        $project_address = '';
        if($project->address) {
            if($project->address->first()) {
                $project_address = $project->address->first()->address . ' ' . ucwords(strtolower($project->address->first()->district)) . ' Provinsi ' . ucwords(strtolower($project->address->first()->prov));
            }
        }

        // === PROJECT DESCRIPTION === //
        $project_description_table = new Table();
        $project_description_table->addRow();
        $project_description_cell = $project_description_table->addCell();
        $project_description_content = $project->description ? $project->description : '';
        $project_description_content = str_replace('<p>', '<p style="font-family: Bookman Old Style; font-size: 11px;">', $project_description_content);
        Html::addHtml($project_description_cell, $this->replaceHtmlList($project_description_content));

        $templateProcessor = new TemplateProcessor(storage_path('app/public/template/template_ka_andal.docx'));

        $templateProcessor->setValue('project_title', ucwords(strtolower($project->project_title)));
        $templateProcessor->setValue('pic', $project->initiator->name);
        $templateProcessor->setComplexBlock('description', $project_description_table);
        $templateProcessor->setValue('project_address', $project_address);
        $templateProcessor->cloneBlock('penyusun', count($penyusun), true, false, $penyusun);
        $templateProcessor->cloneBlock('positive', count($positive), true, false, $positive);
        $templateProcessor->cloneBlock('negative', count($negative), true, false, $negative);

        $positive_no_html = 0;
        $negative_no_html = 0;
        foreach ($publicConsultation as $p) {

            if($p->positive_feedback_summary) {
                $positive_feedback = $p->positive_feedback_summary;
                $positiveTable = new Table();
                $positiveTable->addRow();
                $cell = $positiveTable->addCell();
                Html::addHtml($cell, $this->replaceHtmlList($positive_feedback));

                $templateProcessor->setComplexBlock('positive-' . $positive_no_html, $positiveTable);
                $positive_no_html++;
            }

            if($p->negative_feedback_summary) {
                $negative_feedback = $p->negative_feedback_summary;
                $negativeTable = new Table();
                $negativeTable->addRow();
                $cell = $negativeTable->addCell();
                Html::addHtml($cell, $this->replaceHtmlList($negative_feedback));

                $templateProcessor->setComplexBlock('negative-' . $negative_no_html, $negativeTable);
                $negative_no_html;
            }
        }

        $templateProcessor->cloneRowAndSetValues('pra_konstruksi', $pra_konstruksi);
        $templateProcessor->cloneRowAndSetValues('konstruksi', $konstruksi);
        $templateProcessor->cloneRowAndSetValues('operasi', $operasi);
        $templateProcessor->cloneRowAndSetValues('pasca_operasi', $pasca_operasi);
        $templateProcessor->cloneRowAndSetValues('metode_studi', $metode_studi);

        // === HTML CONTENT OVERWRITE === //
        for($i = 0; $i < count($html_content); $i++) {
            $templateProcessor->setComplexBlock($html_content[$i]['name'], $html_content[$i]['content']);
        }

        // === METODE STUDI === //
        if(count($metode_replace) > 0) {
            for($mri = 0; $mri < count($metode_replace); $mri++) {
                $templateProcessor->setComplexBlock($metode_replace[$mri]['replace'], $metode_replace[$mri]['data']);
            }
        }

        if($type == 'andal') {
            // $templateProcessor->saveAs(Storage::disk('public')->path('formulir/' . $save_file_name));
            $tmpName = $templateProcessor->save();
            Storage::disk('public')->put('formulir/' . $save_file_name, file_get_contents($tmpName));
            unlink($tmpName);
        } else {
            // $templateProcessor->saveAs(Storage::disk('public')->path('workspace/' . $save_file_name));
            $tmpName = $templateProcessor->save();
            Storage::disk('public')->put('workspace/' . $save_file_name, file_get_contents($tmpName));
            unlink($tmpName);
        }

        $attachment_type = $type === 'andal' ? 'Formulir KA Andal' : 'Formulir KA';
        $document_attachment = DocumentAttachment::where([['id_project', $project->id],['type', $attachment_type]])->first();
        if(!$document_attachment) {
            $document_attachment = new DocumentAttachment();
            $document_attachment->id_project = $project->id;
            $document_attachment->type = $attachment_type;

            if($type == 'andal') {
                $document_attachment->attachment = 'formulir/' . $save_file_name;
            } else {
                $document_attachment->attachment = 'workspace/' . $save_file_name;
            }

            $document_attachment->save();
        }

        // get pdf url
        $pdf_url = $this->docxToPdf($document_attachment->attachment);

        return [
            'file_name' => $document_attachment->attachment,
            'project_title' => strtolower(str_replace('/', '-', $project->project_title)),
            'pdf_url' => $pdf_url,
            'file_name_ka' => $save_file_name,
        ];
    }

    private function getFormulirIm(
                        $s,
                        $total,
                        $changeType,
                        $ronaAwal,
                        $component,
                        $pA,
                        $ed_besaran_rencana_title,
                        $ed_kondisi_rona_title,
                        $ed_pengaruh_rencana_title,
                        $ed_intensitas_perhatian_title,
                        $ed_kesimpulan_title) {
        return [
            str_replace(' ', '_', strtolower($s->name)) => $total + 1,
            'component_name' => "$changeType $ronaAwal akibat $component",
            'rencana' => '${' . 'rencana_' . $s->id . '_' . $pA->id . '}',
            'rona_lingkungan' => $ronaAwal,
            'dampak_potensial' => "$changeType $ronaAwal akibat $component",
            'ed_besaran_rencana_title' => $ed_besaran_rencana_title,
            'ed_besaran_rencana' => '${' . 'ed_besaran_rencana_' . $s->id . '_' . $pA->id . '}',
            'ed_kondisi_rona_title' => $ed_kondisi_rona_title,
            'ed_kondisi_rona' => '${' . 'ed_kondisi_rona_' . $s->id . '_' . $pA->id . '}',
            'ed_pengaruh_rencana_title' => $ed_pengaruh_rencana_title,
            'ed_pengaruh_rencana' => '${' . 'ed_pengaruh_rencana_' . $s->id . '_' . $pA->id . '}',
            'ed_intensitas_perhatian_title' => $ed_intensitas_perhatian_title,
            'ed_intensitas_perhatian' => '${' . 'ed_intensitas_perhatian_' . $s->id . '_' . $pA->id . '}',
            'ed_kesimpulan_title' => $ed_kesimpulan_title,
            'ed_kesimpulan' => '${' . 'ed_kesimpulan_' . $s->id . '_' . $pA->id . '}',
            'dph' => $pA->is_hypothetical_significant ? 'DPH' : 'DTPH',
            'batas_wilayah' => $pA->study_location ?? '',
            'batas_waktu' => $pA->study_length_year . ' tahun ' . $pA->study_length_month . ' bulan'
        ];
    }

    private function renderHtml($name, $stage_id, $impact_id, $width, $data, $font = null)
    {
        $table = new Table();
        $table->addRow();
        $cell = $table->addCell($width);
        $selected_font = $font ? $font : 'Bookman Old Style';
        $content = '';
        if($data) {
            $content = str_replace('<p>', '<p style="font-family: Bookman Old Style; font-size: 9.5px;">', $this->replaceHtmlList($data, $selected_font));
        }
        Log::info($content);
        Html::addHtml($cell, $content);
        return [
            'name' => '${' . $name . '_' . $stage_id . '_' . $impact_id . '}',
            'content' => $table
        ];
    }

    private function renderHtmlTable($data, $width = null, $font = null, $font_size = null)
    {
        $table = new Table();
        $table->addRow();
        $cell = null;
        if($width) {
            $cell = $table->addCell($width);
        } else {
            $cell = $table->addCell();
        }
        $selected_font = $font ? $font : 'Bookman Old Style';
        $selected_font_size = $font_size ? $font_size : '9.5';
        $content = '';
        if($data) {
            $content = str_replace('<p>', '<p style="font-family: ' . $selected_font . '; font-size: ' . $selected_font_size . 'px;">', $this->replaceHtmlList($data));
        }
        Html::addHtml($cell, $content);
        return $table;
    }

    private function removeNestedParagraph($data)
    {
        $old_data = $data;
        $new_data = null;

        while(true) {
            $new_data = preg_replace('/(.*<p>)(((?!<\/p>).)*?)(<p>)(.*?)(<\/p>)(.*)/', '\1\2\5\7', $old_data);
            if($new_data == $old_data) {
                break;
            } else {
                $old_data = $new_data;
            }
        }

        return $new_data;
    }

    private function replaceHtmlList($data, $font = 'Bookman Old Style')
    {
        if($data) {
            $removed_nested_p = $this->removeNestedParagraph($data);
            return ListRender::parsingList($removed_nested_p, $font, '11px');
        } else {
            return '';
        }
    }

    private function getComponentRonaAwal($imp, $id_project_stage, $type)
    {
        $component = null;
        $ronaAwal = null;

        if($type == 'andal') {
            if ($imp->projectComponent) {
                if ($imp->projectComponent->component->id_project_stage == $id_project_stage) {
                    if ($imp->projectRonaAwal) {
                        $ronaAwal = $imp->projectRonaAwal->rona_awal->name;
                        $component = $imp->projectComponent->component->name;
                    }
                }
            }
        } else {
            if ($imp->component) {
                if ($imp->component->component->id_project_stage == $id_project_stage) {
                    if ($imp->ronaAwal) {
                        $ronaAwal = $imp->ronaAwal->rona_awal->name;
                        $component = $imp->component->component->name;
                    }
                }
            }
        }

        return [
            'component' => $component,
            'ronaAwal' => $ronaAwal
        ];
    }

    private function exportKAPDF($idProject, $type)
    {
        $ids = [4, 1, 2, 3];
        $stages = ProjectStage::select('id', 'name')->get()->sortBy(function ($model) use ($ids) {
            return array_search($model->getKey(), $ids);
        });
        $project = Project::findOrFail($idProject);
        $formulator_team = FormulatorTeam::where('id_project', $project->id)->first();
        $team_member = null;
        if($formulator_team) {
            $team_member = FormulatorTeamMember::where('id_formulator_team', $formulator_team->id)->orderBy('position', 'desc')->get();
        }
        $public_consultation = PublicConsultation::select('id', 'project_id', 'positive_feedback_summary', 'negative_feedback_summary')
            ->where('project_id', $project->id)->get();

        $pelingkupan = [];

        $im = null;

        if($type == 'andal') {
            $im = ImpactIdentificationClone::select('id', 'id_project', 'id_project_component', 'id_change_type', 'id_project_rona_awal', 'initial_study_plan', 'is_hypothetical_significant', 'study_location', 'study_length_year', 'study_length_month')
                ->where('id_project', $project->id)->with('potentialImpactEvaluation.pieParam')->get();
        } else {
            $im = ImpactIdentification::select('id', 'id_project', 'id_project_component', 'id_change_type', 'id_project_rona_awal', 'initial_study_plan', 'is_hypothetical_significant', 'study_location', 'study_length_year', 'study_length_month')
                ->where('id_project', $project->id)->with('potentialImpactEvaluation.pieParam')->get();
        }

        $total_ms = 0;
        foreach ($stages as $s) {
            $total = 0;
            $pelingkupan[str_replace(' ', '_', strtolower($s->name))] = [];
            foreach ($im as $pA) {
                $ronaAwal = '';
                $component = '';

                $data = $this->getComponentRonaAwal($pA, $s->id, $type);

                if ($data['component'] && $data['ronaAwal']) {
                    $ronaAwal = $data['ronaAwal'];
                    $component = $data['component'];
                } else {
                    continue;
                }

                $changeType = $pA->id_change_type ? $pA->changeType->name : '';

                // ======= POTENTIAL IMPACT EVALUATIONS ======= //
                $ed_besaran_rencana_title = '';
                $ed_besaran_rencana = '';
                $ed_kondisi_rona_title = '';
                $ed_kondisi_rona = '';
                $ed_pengaruh_rencana_title = '';
                $ed_pengaruh_rencana = '';
                $ed_intensitas_perhatian_title = '';
                $ed_intensitas_perhatian = '';
                $ed_kesimpulan_title = '';
                $ed_kesimpulan = '';

                if ($pA->potentialImpactEvaluation) {
                    foreach ($pA->potentialImpactEvaluation as $po) {
                        if ($po->id_pie_param == 1) {
                            $ed_besaran_rencana_title = $po->pieParam->name;
                            $ed_besaran_rencana = $po->text;
                        } else if ($po->id_pie_param == 2) {
                            $ed_kondisi_rona_title = $po->pieParam->name;
                            $ed_kondisi_rona = $po->text;
                        } else if ($po->id_pie_param == 3) {
                            $ed_pengaruh_rencana_title = $po->pieParam->name;
                            $ed_pengaruh_rencana = $po->text;
                        } else if ($po->id_pie_param == 4) {
                            $ed_intensitas_perhatian_title = $po->pieParam->name;
                            $ed_intensitas_perhatian = $po->text;
                        } else if ($po->id_pie_param == 5) {
                            $ed_kesimpulan_title = $po->pieParam->name;
                            $ed_kesimpulan = $po->text;
                        }
                    }
                }

                $pelingkupan[str_replace(' ', '_', strtolower($s->name))][] = [
                    'no' => $total + 1,
                    'component_name' => "$changeType $ronaAwal akibat $component",
                    'rencana' => $pA->initial_study_plan ?? '',
                    'rona_lingkungan' => $ronaAwal,
                    'dampak_potensial' => "$changeType $ronaAwal akibat $component",
                    'ed_besaran_rencana_title' => $ed_besaran_rencana_title,
                    'ed_besaran_rencana' => $ed_besaran_rencana,
                    'ed_kondisi_rona_title' => $ed_kondisi_rona_title,
                    'ed_kondisi_rona' => $ed_kondisi_rona,
                    'ed_pengaruh_rencana_title' => $ed_pengaruh_rencana_title,
                    'ed_pengaruh_rencana' => $ed_pengaruh_rencana,
                    'ed_intensitas_perhatian_title' => $ed_intensitas_perhatian_title,
                    'ed_intensitas_perhatian' => $ed_intensitas_perhatian,
                    'ed_kesimpulan_title' => $ed_kesimpulan_title,
                    'ed_kesimpulan' => $ed_kesimpulan,
                    'dph' => $pA->is_hypothetical_significant ? 'DPH' : 'DTPH',
                    'batas_wilayah' => $pA->study_location ?? '',
                    'batas_waktu' => $pA->study_length_year . ' tahun ' . $pA->study_length_month . ' bulan'
                ];

                if ($pA->is_hypothetical_significant && $pA->impactStudy) {
                    $pelingkupan['metode_studi'][] = [
                        'no' => $total_ms + 1,
                        'potential_impact_evaluation' => "$changeType $ronaAwal akibat $component",
                        'required_information' => $pA->impactStudy->required_information ?? '',
                        'data_gathering_method' => $pA->impactStudy->data_gathering_method ?? '',
                        'analysis_method' =>  $pA->impactStudy->analysis_method ?? '',
                        'forecast_method' => $pA->impactStudy->forecast_method ?? '',
                        'evaluation_method' => $pA->impactStudy->evaluation_method ?? ''
                    ];
                    $total_ms++;
                }
                $total++;
            }
        }

        $pdf = PDF::loadView('document.template_ka_andal',
            compact(
                'project',
                'team_member',
                'public_consultation',
                'pelingkupan'
            ))->setPaper('a4', 'landscape');

        return $pdf->download('hehey.pdf');
    }

    private function getComments($id)
    {
        $komen = Comment::where([['id_impact_identification', $id], ['document_type', 'andal'], ['reply_to', null]])
            ->orderBY('id', 'DESC')->get();

        $comments = [];
        foreach ($komen as $c) {
            $replies = [];

            if ($c->reply) {
                foreach ($c->reply as $r) {
                    $replies[] = [
                        'id' => $r->id,
                        'created_at' => $r->updated_at->locale('id')->isoFormat('D MMMM Y hh:mm:ss'),
                        'description' => $r->description
                    ];
                }
            }

            $comments[] = [
                'id' => $c->id,
                'id_impact_identification' => $c->id_impact_identification,
                'created_at' => $c->updated_at->locale('id')->isoFormat('D MMMM Y hh:mm:ss'),
                'user' => $c->user->name,
                'is_checked' => $c->is_checked,
                'description' => $c->description,
                'column_type' => $c->column_type,
                'replies' => $replies
            ];
        }

        return $comments;
    }

    private function dateDifference($date1, $date2) {
        if(($date1 && $date2) && ($date2 > $date1)) {
            $date1 = date_create(date('Y-m-d', strtotime($date1)));
            $date2 = date_create(date('Y-m-d', strtotime($date2)));

            $diff = date_diff($date1, $date2);

            return $diff->y . ' Tahun ' . $diff->m . ' Bulan';
        }

        return '';
    }

    private function getAttachment($id_project)
    {
        $project = Project::findOrFail($id_project);
        $pertek = AndalAttachment::where([['id_project', $id_project],['is_pertek', true],['is_andal', true]])->get();
        $others = AndalAttachment::where([['id_project', $id_project],['is_pertek', false],['is_andal', true]])->get();

        return [
            'kesesuaian_tata_ruang' => $project->ktr,
            'persetujuan_awal' => $project->pre_agreement_file,
            'pippib' => $project->ppib_file,
            'kawasan_lindung' => $project->kawasan_lindung_file,
            'pertek' => $pertek ? $pertek->toArray() : [],
            'lainnya' => $others ? $others->toArray() : [],
        ];
    }

    private function base64ToFile($file_64)
    {
        $extension = explode('/', explode(':', substr($file_64, 0, strpos($file_64, ';')))[1])[1];   // .jpg .png .pdf

        $replace = substr($file_64, 0, strpos($file_64, ',')+1);

        // find substring fro replace here eg: data:image/png;base64,

        $file = str_replace($replace, '', $file_64);

        $file = str_replace(' ', '+', $file);

        return [
            'extension' => $extension,
            'file' => base64_decode($file)
        ];
    }

    private function documentReview($id_project)
    {
        $project = Project::findOrFail($id_project);
        $document_attachment = DocumentAttachment::where([['id_project', $id_project],['type', 'Dokumen Andal']])->first();
        $pdf_url = null;
        $fileDoc = Storage::disk('public')->size('workspace/' . strtolower(str_replace('/', '-', $project->id)) . '-andal'  . '.docx');
        if ($fileDoc < 100000000) {
            $pdf_url = $this->docxToPdf($document_attachment->attachment);
        }

        return response()->json([
            'docx_url' => $document_attachment->attachment,
            'pdf_url' => $pdf_url,
            'project_title' => strtolower(str_replace('/', '-', $project->project_title))
        ]);
    }

    private function docxToPdf($url)
    {
        $downloadUri = url($url);
        $key = Document::GenerateRevisionId($downloadUri);
        $convertedUri;
        $download_url = Document::GetConvertedUri($downloadUri, 'docx', 'pdf', $key, FALSE, $convertedUri);
        return $convertedUri;
    }
}
