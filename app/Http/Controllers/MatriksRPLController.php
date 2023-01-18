<?php

namespace App\Http\Controllers;

use App\Entity\EnvImpactAnalysis;
use App\Entity\EnvManagePlan;
use App\Entity\Comment;
use App\Entity\EnvMonitorPlan;
use App\Entity\EnvPlanForm;
use App\Entity\EnvPlanIndicator;
use App\Entity\EnvPlanInstitution;
use App\Entity\EnvPlanLocation;
use App\Entity\EnvPlanSource;
use App\Entity\ImpactIdentification;
use App\Entity\ImpactIdentificationClone;
use App\Entity\Institution;
use App\Entity\Project;
use App\Entity\ProjectStage;
use ErrorException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MatriksRPLController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->docs) {
            $poinA = [];
            $poinB = [];

            $ids = [4,1,2,3];
            $stages = ProjectStage::select('id', 'name')->get()->sortBy(function($model) use($ids) {
                return array_search($model->getKey(),$ids);
            });

            $isExist = Project::where('id', $request->idProject)->whereHas('impactIdentificationsClone', function($query) {
                $query->whereHas('envMonitorPlan');
            })->first();

            if($isExist) {
                $poinA = $this->getPoinA($stages, $request->idProject);
                $poinB = $this->getPoinB($stages, $request->idProject);
            }

            return [
                'poinA' => $poinA,
                'poinB' => $poinB
            ];
        }

        if($request->institution) {
            return Institution::all();
        }

        if($request->project) {
            return Project::whereHas('impactIdentifications', function($q) {
                $q->whereHas('envImpactAnalysis');
            })->get();
        }

        if($request->lastTime && $request->idProject) {
            $id_project = $request->idProject;
            $time = EnvMonitorPlan::whereHas('impactIdentification', function($q) use($id_project) {
                $q->where('id_project', $id_project);
            })->orderBy('updated_at', 'DESC')->first();

            if($time) {
                return 'Diperbarui ' . $time->updated_at->locale('id')->diffForHumans();
            } else {
                return null;
            }
        }

        if($request->idProject) {
            $ids = [4,1,2,3];
            $stages = ProjectStage::select('id', 'name')->get()->sortBy(function($model) use($ids) {
                return array_search($model->getKey(),$ids);
            });

            $project = Project::where('id', $request->idProject)->whereHas('impactIdentificationsClone', function($query) {
                $query->whereHas('envMonitorPlan');
            })->first();

            $type = $project ? 'update' : 'new';

            return $this->getEnvMonitorPlan($request->idProject, $stages, $type);
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
        // if($request->map) {
        //     if($request->hasFile('map_file')) {
        //         //create file
        //         $file = $request->file('map_file');
        //         $name = '/map/' . uniqid() . '.' . $file->extension();
        //         $file->storePubliclyAs('public', $name);

        //         $project = Project::findOrFail($request->idProject);
        //         $project->map_rpl = Storage::url($name);
        //         $project->save();

        //         return response()->json(['message' => 'success']);
        //    }

        //    return response()->json(['message' => 'failed'], 404);
        // }

        if($request->type == 'impact-comment') {
            $comment = new Comment();
            $comment->id_user = $request->id_user;
            $comment->id_impact_identification = $request->id_impact_identification;
            $comment->description = $request->description;
            $comment->column_type = $request->column_type;
            $comment->document_type = 'rpl';
            $comment->is_checked = false;
            $comment->id_project = $request->id_project;
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

        if($request->type == 'impact-comment-reply') {
            $comment = new Comment();
            $comment->id_user = $request->id_user;
            $comment->id_impact_identification = $request->id_impact_identification;
            $comment->description = $request->description;
            $comment->document_type = 'rpl';
            $comment->reply_to = $request->id_comment;
            $comment->id_project = $request->id_project;
            $comment->save();

            return [
                'id' => $comment->id,
                'created_at' => $comment->updated_at->locale('id')->isoFormat('D MMMM Y hh:mm:ss'),
                'description' => $comment->description,
                'role' => Auth::user()->roles->first()->name == 'formulator' ? 'Catatan Balasan Penyusun' : 'Catatan Balasan Penguji'
            ];
        }

        if($request->type == 'checked-comment') {
            $comment = Comment::findOrFail($request->id);
            if($comment->is_checked) {
                $comment->is_checked = false;
            } else {
                $comment->is_checked = true;
            }
            $comment->save();

            return $comment->is_checked;
        }

        $monitor = $request->monitor;
        $envMonitor = null;
        $ids = [];

        for($i = 0; $i < count($monitor); $i++) {
            if($request->type == 'new') {
                $envMonitor = new EnvMonitorPlan();
                $envMonitor->id_impact_identifications = $monitor[$i]['id'];
            } else {
                $envMonitor = EnvMonitorPlan::where('id_impact_identifications', $monitor[$i]['id'])->first();
                if(!$envMonitor) {
                    $envMonitor = new EnvMonitorPlan();
                    $envMonitor->id_impact_identifications = $monitor[$i]['id'];
                }

                $ids[] = $monitor[$i]['id'];
            }

            $envMonitor->time_frequent = $monitor[$i]['period_number'] . '-' . $monitor[$i]['period_description'];
            $envMonitor->save();

            // === INSTITUTION === //
            $institution = EnvPlanInstitution::where('id_impact_identification', $monitor[$i]['id'])->first();
            if(!$institution) {
                $institution = new EnvPlanInstitution();
                $institution->id_impact_identification = $monitor[$i]['id'];
            }

            $institution->executor = $monitor[$i]['executor'];
            $institution->supervisor = $monitor[$i]['supervisor'];
            $institution->report_recipient = $monitor[$i]['report_recipient'];
            $institution->save();

            // === IMPACT SOURCE === //
            $impact_source = $monitor[$i]['impact_source'];
            if(count($impact_source) > 0) {
                for($a = 0; $a < count($impact_source); $a++) {
                    $imp_source = null;
                    if($impact_source[$a]['id'] !== null) {
                        $imp_source = EnvPlanSource::findOrFail($impact_source[$a]['id']);
                    } else {
                       $imp_source = new EnvPlanSource();
                       $imp_source->id_impact_identification = $monitor[$i]['id'];
                    }

                    $imp_source->description = $impact_source[$a]['description'];
                    $imp_source->save();
                }
            }

            // === SUCCESS INDICATOR === //
            $indicator = $monitor[$i]['indicator'];
            if(count($indicator) > 0) {
                for($a = 0; $a < count($indicator); $a++) {
                    $imp_indicator = null;
                    if($indicator[$a]['id'] !== null) {
                        $imp_indicator = EnvPlanIndicator::findOrFail($indicator[$a]['id']);
                    } else {
                       $imp_indicator = new EnvPlanIndicator();
                       $imp_indicator->id_impact_identification = $monitor[$i]['id'];
                    }

                    $imp_indicator->description = $indicator[$a]['description'];
                    $imp_indicator->save();
                }
            }

            // === COLLECTION METHOD === //
            $collection_method = $monitor[$i]['collection_method'];
            if(count($collection_method) > 0) {
                for($a = 0; $a < count($collection_method); $a++) {
                    $collection_method_data = null;
                    if($collection_method[$a]['id'] !== null) {
                        $collection_method_data = EnvPlanForm::findOrFail($collection_method[$a]['id']);
                    } else {
                       $collection_method_data = new EnvPlanForm();
                       $collection_method_data->id_env_monitor_plan = $envMonitor->id;
                    }

                    $collection_method_data->description = $collection_method[$a]['description'];
                    $collection_method_data->save();
                }
            }

            // === LOCATION === //
            $location = $monitor[$i]['location'];
            if(count($location) > 0) {
                for($a = 0; $a < count($location); $a++) {
                    $location_data = null;
                    if($location[$a]['id'] !== null) {
                        $location_data = EnvPlanLocation::findOrFail($location[$a]['id']);
                    } else {
                       $location_data = new EnvPlanLocation();
                       $location_data->id_env_monitor_plan = $envMonitor->id;
                    }

                    $location_data->description = $location[$a]['description'];
                    $location_data->save();
                }
            }
        }

         // DELETE IMPACT SOURCE
         $delete_ip = $request->deletedImpactSource;
         if(count($delete_ip) > 0) {
             EnvPlanSource::whereIn('id', $delete_ip)->delete();
         }

         // DELETE INDICATOR
         $delete_i = $request->deletedIndicator;
         if(count($delete_i) > 0) {
             EnvPlanIndicator::whereIn('id', $delete_i)->delete();
         }

         // DELETE COLLECTION METHOD
         $delete_cm = $request->deletedCollectionMethod;
         if(count($delete_cm) > 0) {
             EnvPlanForm::whereIn('id', $delete_cm)->delete();
         }

         // DELETE LOCATION
         $delete_loc = $request->deletedLocation;
         if(count($delete_loc) > 0) {
             EnvPlanLocation::whereIn('id', $delete_loc)->delete();
         }

        // === WORKFLOW === //
        $project = Project::findOrFail($request->idProject);
        if($project->marking == 'amdal.andal-drafting') {
            $project->workflow_apply('draft-amdal-rklrpl');
            $project->save();
        }

        if($request->type != 'new') {
            $lastTime = EnvMonitorPlan::whereIn('id_impact_identifications', $ids)->orderBy('updated_at', 'DESC')->first()
                            ->updated_at->locale('id')->diffForHumans();
            return 'Diperbarui ' . $lastTime;
        }

        return 'Diperbarui ' . EnvMonitorPlan::orderBy('id', 'DESC')->first()->updated_at->locale('id')->diffForHumans();
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

    private function getEnvMonitorPlan($id_project, $stages, $type) {
        $results = [];

        // ================== POIN A ================= //
        $poinA = ImpactIdentificationClone::select('id', 'id_project', 'id_project_component', 'id_change_type', 'id_project_rona_awal')
        ->where([['id_project', $id_project],['is_hypothetical_significant', true]])->whereHas('envImpactAnalysis', function($q) {
            $q->whereHas('detail', function($query) {
                $query->where('important_trait', '+P')->orWhere('important_trait', '-P');
            });
        })->get();
        $results[] = [
            'id' => 1,
            'no' => 'A. Dampak Penting Yang Dipantau (Hasil Arahan Pengelolaan pada ANDAL)',
            'type' => 'master-title'
         ];
        $results = $this->getLoopData($stages, $poinA, $results, $type);

        //  =========== POIN B ============= //
        $results[] = [
            'id' => 2,
            'no' => 'B. Dampak Lingkungan Lainnya yang Dipantau',
            'type' => 'master-title'
         ];

         $idPoinA = [];
         foreach($poinA as $pA) {
             $idPoinA[] = $pA->id;
         }

        $results = $this->getLoopDataB($stages, $id_project, $results, $type, $idPoinA);

        return $results;
    }

    private function getLoopData($stages, $data, $results, $type) {
        $alphabet_list = 'A';

        foreach($stages as $s) {
            $results[] = [
                'id' => $s->id,
                'name' => $alphabet_list . '. ' . $s->name,
                'type' => 'title'
            ];

            $total = 0;

            foreach($data as $pA) {
                $ronaAwal = '';
                $component = '';
                $periodeNumber = '0';
                $periodeDesc = '';


                // check stages
                $id_stages = null;

                if($pA->projectComponent) {
                    $id_stages = $pA->projectComponent->component->id_project_stage;

                    if($id_stages == $s->id) {
                        if($pA->projectRonaAwal) {
                            $ronaAwal = $pA->projectRonaAwal->rona_awal->name;
                            $component = $pA->projectComponent->component->name;
                        } else {
                            continue;
                        }
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }

                $changeType = $pA->id_change_type ? $pA->changeType->name : '';

                $comments = $this->getComments($pA->id);

                if(!$pA->envMonitorPlan) {
                    $type = 'new';
                }

                // PERIODE NUMBER & DESCRIPTION
                if($type != 'new') {
                    if($pA->envMonitorPlan->time_frequent) {
                        $split_period = explode('-', $pA->envMonitorPlan->time_frequent);
                        if(is_numeric($split_period[0])) {
                            $periodeNumber = $split_period[0];
                        }
                        if(count($split_period) > 1) {
                            $periodeDesc = $split_period[1];
                        }
                    }
                }

                // === INSTITUTION === //
                $institution = EnvPlanInstitution::where('id_impact_identification', $pA->id)->first();

                // === IMPACT SOURCE === //
                $impact_source = EnvPlanSource::select('id', 'description', 'id_impact_identification')->where('id_impact_identification', $pA->id)->get();

                // === INDICATOR == //
                $indicator = EnvPlanIndicator::select('id', 'description', 'id_impact_identification')->where('id_impact_identification', $pA->id)->get();

                $results[] = [
                    'no' => $total + 1,
                    'id' => $pA->id,
                    'name' => "$changeType $ronaAwal akibat $component",
                    'type' => 'subtitle',
                    'impact_source' => $impact_source,
                    'indicator' => $indicator,
                    'stages' => $s->name,
                    'collection_method' =>
                        $type == 'new' ?
                        [] :
                        EnvPlanForm::select('id', 'description', 'id_env_monitor_plan')
                                        ->where('id_env_monitor_plan', $pA->envMonitorPlan->id)
                                        ->get(),
                    'location' =>
                        $type == 'new' ?
                        [] :
                        EnvPlanLocation::select('id', 'description', 'id_env_monitor_plan')
                                        ->where('id_env_monitor_plan', $pA->envMonitorPlan->id)
                                        ->get(),
                    'time_frequent' => $type == 'new' ? null : $pA->envMonitorPlan->time_frequent,
                    'executor' => $institution ? $institution->executor : null,
                    'supervisor' => $institution ? $institution->supervisor : null,
                    'report_recipient' => $institution ? $institution->report_recipient : null,
                    'description' => $type == 'new' ? null : $pA->envMonitorPlan->description,
                    'period_number' => $periodeNumber,
                    'period_description' => $periodeDesc,
                    'comments' => $comments
                ];
                $results[] = [
                    'id' => $pA->id,
                    'type' => 'comments'
                ];
                $total++;
            }

            if($total == 0) {
                array_pop($results);
            } else {
                $alphabet_list++;
            }
        }

        return $results;
    }

    private function getLoopDataB($stages, $id_project, $results, $type, $idPoinA) {
         //  =========== POIN B.1 ============= //
        // DAMPAK TIDAK PENTING HIPOTETIK YANG DIKELOLA (DTPHK)
        $poinB1 = ImpactIdentificationClone::select('id', 'id_project', 'id_project_component', 'id_change_type', 'id_project_rona_awal')
        ->where([['id_project', $id_project],['is_managed', true],['is_hypothetical_significant', '!=', true]])->get();

        //  =========== POIN B.2 ============= //
        // DAMPAK TIDAK PENTING HIPOTETIK YANG DIKELOLA (DTPHK)
        $poinB2 = ImpactIdentificationClone::select('id', 'id_project', 'id_project_component', 'id_change_type', 'id_project_rona_awal')
            ->where([['id_project', $id_project],['is_managed', false],['initial_study_plan', '!=', null],['is_hypothetical_significant', '!=', true]])->get();

        // ============ POIN B.3 ============= //
        // DAMPAK TIDAK PENTING (HASIL MATRIK ANDAL (TP))
        if(count($idPoinA) > 0) {
            $poinB3 = ImpactIdentificationClone::select('id', 'id_project', 'id_project_component', 'id_change_type', 'id_project_rona_awal')
            ->where([['id_project', $id_project], ['is_hypothetical_significant', true]])->whereNotIn('id', $idPoinA)->whereHas('envImpactAnalysis')->get();
        } else {
            $poinB3 = ImpactIdentificationClone::select('id', 'id_project', 'id_project_component', 'id_change_type', 'id_project_rona_awal')
            ->where([['id_project', $id_project], ['is_hypothetical_significant', true]])->whereHas('envImpactAnalysis')->get();
        }

        $data_merge_1 = $poinB1->merge($poinB2);
        $data_merge_final = $data_merge_1->merge($poinB3);

        $alphabet_list = 'A';

        foreach($stages as $s) {
            $results[] = [
                'id' => $s->id,
                'name' => $alphabet_list . '. ' . $s->name,
                'type' => 'title'
            ];

            $total = 0;

            foreach($data_merge_final as $merge) {
                $ronaAwal = '';
                $component = '';
                $periodeNumber = '0';
                $periodeDesc = '';

                // check stages
                $id_stages = null;

                if($merge->projectComponent) {
                    $id_stages = $merge->projectComponent->component->id_project_stage;

                    if($id_stages == $s->id) {
                        if($merge->projectRonaAwal) {
                            $ronaAwal = $merge->projectRonaAwal->rona_awal->name;
                            $component = $merge->projectComponent->component->name;
                        } else {
                            continue;
                        }
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }

                $changeType = $merge->id_change_type ? $merge->changeType->name : '';

                $comments = $this->getComments($merge->id);

                if(!$merge->envMonitorPlan) {
                    $type = 'new';
                }

                // PERIODE NUMBER & DESCRIPTION
                if($type != 'new') {
                    if($merge->envMonitorPlan->time_frequent) {
                        $split_period = explode('-', $merge->envMonitorPlan->time_frequent);
                        if(is_numeric($split_period[0])) {
                            $periodeNumber = $split_period[0];
                        }
                        if(count($split_period) > 1) {
                            $periodeDesc = $split_period[1];
                        }
                    }
                }

                // === INSTITUTION === //
                $institution = EnvPlanInstitution::where('id_impact_identification', $merge->id)->first();

                 // === IMPACT SOURCE === //
                 $impact_source = EnvPlanSource::select('id', 'description', 'id_impact_identification')->where('id_impact_identification', $merge->id)->get();

                 // === INDICATOR === //
                 $indicator = EnvPlanIndicator::select('id', 'description', 'id_impact_identification')->where('id_impact_identification', $merge->id)->get();

                $results[] = [
                    'no' => $total + 1,
                    'id' => $merge->id,
                    'name' => "$changeType $ronaAwal akibat $component",
                    'type' => 'subtitle',
                    'impact_source' => $impact_source,
                    'indicator' => $indicator,
                    'stages' => $s->name,
                    'collection_method' =>
                        $type == 'new' ?
                        [] :
                        EnvPlanForm::select('id', 'description', 'id_env_monitor_plan')
                                        ->where('id_env_monitor_plan', $merge->envMonitorPlan->id)
                                        ->get(),
                    'location' =>
                        $type == 'new' ?
                        [] :
                        EnvPlanLocation::select('id', 'description', 'id_env_monitor_plan')
                                        ->where('id_env_monitor_plan', $merge->envMonitorPlan->id)
                                        ->get(),
                    'time_frequent' => $type == 'new' ? null : $merge->envMonitorPlan->time_frequent,
                    'executor' => $institution ? $institution->executor : null,
                    'supervisor' => $institution ? $institution->supervisor : null,
                    'report_recipient' => $institution ? $institution->report_recipient : null,
                    'description' => $type == 'new' ? null : $merge->envMonitorPlan->description,
                    'period_number' => $periodeNumber,
                    'period_description' => $periodeDesc,
                    'comments' => $comments
                ];
                $results[] = [
                    'id' => $merge->id,
                    'type' => 'comments'
                ];
                $total++;
            }

            if($total == 0) {
                array_pop($results);
            } else {
                $alphabet_list++;
            }
        }

        return $results;
    }

    private function getPoinA($stages, $id_project) {
         $results = [];

         $poinA = ImpactIdentificationClone::select('id', 'id_project', 'id_project_component', 'id_change_type', 'id_project_rona_awal')
         ->where([['id_project', $id_project],['is_hypothetical_significant', true]])->whereHas('envImpactAnalysis', function($q) {
             $q->whereHas('detail', function($query) {
                 $query->where('important_trait', '+P')->orWhere('important_trait', '-P');
             });
         })->get();

         $alphabet_list = 'A';

         $idx = 0;
         foreach($stages as $s) {
             $results[$idx]['stages'] =  $alphabet_list . '. ' . $s->name;
             $results[$idx]['data'] = [];

             $total = 0;

             foreach($poinA as $pA) {
                $ronaAwal = '';
                $component = '';

                $data = $this->getComponentRonaAwal($pA, $s->id);

                if($data['component'] && $data['ronaAwal']) {
                    $ronaAwal = $data['ronaAwal'];
                    $component = $data['component'];
                } else {
                    continue;
                }

                $changeType = $pA->id_change_type ? $pA->changeType->name : '';

                if(!$pA->envMonitorPlan) {
                    continue;
                }

                $results[$idx]['data'][] = [
                    'no' => $total + 1,
                    'id' => $pA->id,
                    'name' => "$changeType $ronaAwal akibat $component",
                    'type' => 'subtitle',
                    'indicator' => $pA->envMonitorPlan->indicator,
                    'impact_source' => $pA->envMonitorPlan->impact_source,
                    'collection_method' => $pA->envMonitorPlan->collection_method,
                    'location' => $pA->envMonitorPlan->location,
                    'time_frequent' => $pA->envMonitorPlan->time_frequent,
                    'executor' => $pA->envMonitorPlan->executor,
                    'supervisor' => $pA->envMonitorPlan->supervisor,
                    'report_recipient' => $pA->envMonitorPlan->report_recipient,
                    'description' => $pA->envMonitorPlan->description,
                ];
                $total++;
             }
             $idx++;
         }

         return $results;
    }

    private function getPoinB($stages, $id_project) {
        $results = [];

         //  =========== POIN B.1 ============= //
        // DAMPAK TIDAK PENTING HIPOTETIK YANG DIKELOLA (DTPHK)
        $poinB1 = ImpactIdentificationClone::select('id', 'id_project', 'id_project_component', 'id_change_type', 'id_project_rona_awal')
        ->where([['id_project', $id_project],['is_managed', true],['is_hypothetical_significant', '!=', true]])->get();

        //  =========== POIN B.2 ============= //
        // DAMPAK TIDAK PENTING HIPOTETIK YANG DIKELOLA (DTPHK)
        $poinB2 = ImpactIdentificationClone::select('id', 'id_project', 'id_project_component', 'id_change_type', 'id_project_rona_awal')
            ->where([['id_project', $id_project],['is_managed', false],['initial_study_plan', '!=', null],['is_hypothetical_significant', '!=', true]])->get();

        // ============ POIN B.3 ============= //
        // DAMPAK TIDAK PENTING (HASIL MATRIK ANDAL (TP))
        $idPoinA = [];
        $poinA = ImpactIdentificationClone::select('id', 'id_project', 'is_hypothetical_significant', 'id_project_component', 'id_project_rona_awal')
        ->where([['id_project', $id_project],['is_hypothetical_significant', true]])->whereHas('envImpactAnalysis', function($q) {
            $q->whereHas('detail', function($query) {
                $query->where('important_trait', '+P')->orWhere('important_trait', '-P');
            });
        })->get();

        foreach($poinA as $pA) {
            $idPoinA[] = $pA->id;
        }
        if(count($idPoinA) > 0) {
            $poinB3 = ImpactIdentificationClone::select('id', 'id_project', 'id_project_component', 'id_change_type', 'id_project_rona_awal')
            ->where([['id_project', $id_project], ['is_hypothetical_significant', true]])->whereNotIn('id', $idPoinA)->whereHas('envImpactAnalysis')->get();
        } else {
            $poinB3 = ImpactIdentificationClone::select('id', 'id_project', 'id_project_component', 'id_change_type', 'id_project_rona_awal')
            ->where([['id_project', $id_project], ['is_hypothetical_significant', true]])->whereHas('envImpactAnalysis')->get();
        }

        $data_merge_1 = $poinB1->merge($poinB2);
        $data_merge_final = $data_merge_1->merge($poinB3);

        $alphabet_list = 'A';

        $idx = 0;
        foreach($stages as $s) {
            $results[$idx]['stages'] =  $alphabet_list . '. ' . $s->name;
            $results[$idx]['data'] = [];

            $total = 0;

            foreach($data_merge_final as $merge) {
                $ronaAwal = '';
                $component = '';

                $data = $this->getComponentRonaAwal($merge, $s->id);

                if($data['component'] && $data['ronaAwal']) {
                    $ronaAwal = $data['ronaAwal'];
                    $component = $data['component'];
                } else {
                    continue;
                }

                $changeType = $merge->id_change_type ? $merge->changeType->name : '';

                if(!$merge->envMonitorPlan) {
                    continue;
                }

                $results[$idx]['data'][] = [
                    'no' => $total + 1,
                    'id' => $merge->id,
                    'name' => "$changeType $ronaAwal akibat $component",
                    'type' => 'subtitle',
                    'indicator' => $merge->envMonitorPlan->indicator,
                    'impact_source' => $merge->envMonitorPlan->impact_source,
                    'collection_method' => $merge->envMonitorPlan->collection_method,
                    'location' => $merge->envMonitorPlan->location,
                    'time_frequent' => $merge->envMonitorPlan->time_frequent,
                    'executor' => $merge->envMonitorPlan->executor,
                    'supervisor' => $merge->envMonitorPlan->supervisor,
                    'report_recipient' => $merge->envMonitorPlan->report_recipient,
                    'description' => $merge->envMonitorPlan->description,
                ];
                $total++;
            }
            $idx++;
        }

        return $results;
    }

    private function getComponentRonaAwal($imp, $id_project_stage) {
        $component = null;
        $ronaAwal = null;

        try {
            if ($imp->projectComponent) {
                if ($imp->projectComponent->component->id_project_stage == $id_project_stage) {
                    if ($imp->projectRonaAwal) {
                        $ronaAwal = $imp->projectRonaAwal->rona_awal->name;
                        $component = $imp->projectComponent->component->name;
                    }
                }
                // } else if($imp->subProjectComponent->id_project_stage != null) {
                //     if(($imp->subProjectComponent->component) && imp->subProjectComponent->component->id_project_stage == $id_project_stage) {
                //         if($imp->subProjectRonaAwal) {
                //             $ronaAwal = $imp->subProjectRonaAwal->id_rona_awal ? $imp->subProjectRonaAwal->ronaAwal->name : $imp->subProjectRonaAwal->name;
                //             $component = $imp->subProjectComponent->id_component ? $imp->subProjectComponent->component->name : $imp->subProjectComponent->name;
                //         }
                //     }
                // }
            }
        } catch(ErrorException $err) {

        }

        return [
            'component' => $component,
            'ronaAwal' => $ronaAwal
        ];
    }

    private function getComments($id) {
        $komen = Comment::where([['id_impact_identification', $id], ['document_type', 'rpl'],['reply_to', null]])
                        ->orderBY('id', 'DESC')->get();

        $comments = [];
        foreach($komen as $c) {
            $replies = [];

            if($c->reply) {
                foreach($c->reply as $r) {
                    $replies[] = [
                        'id' => $r->id,
                        'created_at' => $r->updated_at->locale('id')->isoFormat('D MMMM Y hh:mm:ss'),
                        'description' => $r->description,
                        'role' => $r->user->roles->first()->name == 'formulator' ? 'Catatan Balasan Penyusun' : 'Catatan Balasan Penguji'
                    ];
                }
                usort($replies, function($a, $b) {
                    return $a['id'] <=> $b['id'];
                });
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
}
