<?php

namespace App\Http\Controllers;

use App\Entity\ImpactIdentification;
use App\Entity\ImpactIdentificationClone;
use App\Entity\ImpactStudy;
use App\Entity\PotentialImpactEvaluation;
use App\Entity\PotentialImpactEvalClone;
use App\Http\Resources\ImpactIdentificationResource;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Entity\ChangeType;
use App\Entity\EnvManagePlan;
use App\Entity\EnvMonitorPlan;
use App\Entity\EnvPlanForm;
use App\Entity\EnvPlanLocation;
use App\Entity\Project;
use PHPUnit\Framework\Constraint\IsEmpty;
use PhpParser\Node\Expr\Empty_;
use App\Entity\SubProject;
use App\Entity\SubProjectComponent;
use App\Entity\SubProjectRonaAwal;
use App\Entity\ProjectComponent;
use App\Entity\ImpactKegiatanLainSekitar;
use App\Entity\ImpactAnalysisDetail;
use App\Entity\SaveStatus;

class ImpactIdentificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $list = ImpactIdentification::all();
        if ($request->id_project) {
            $list = $list->where('id_project', $request->id_project);
        }
        if ($request->id_component) {
            $list = $list->where('id_component', $request->id_component);
        }
        if ($request->id_rona_awal) {
            $list = $list->where('id_rona_awal', $request->id_rona_awal);
        }
        if ($request->join_tables) {
            // versi pelingkupan 22 maret 2022
            $list = ImpactIdentification::with('impactStudy')
                ->select(
                    'impact_identifications.*',
                    'c.id_project_stage as id_project_stage',
                    'c.id_project_stage AS id_project_stage_master',
                    'c.name AS component_name_master',
                    'c.name AS component_name',
                    'ra.name AS rona_awal_name_master',
                    'ra.name AS rona_awal_name',
                    'ct.name AS change_type_name'
                )
                ->leftJoin('project_components AS pc', 'impact_identifications.id_project_component', '=', 'pc.id')
                ->leftJoin('project_rona_awals AS pra', 'impact_identifications.id_project_rona_awal', '=', 'pra.id')
                ->leftJoin('change_types AS ct', 'impact_identifications.id_change_type', '=', 'ct.id')
                ->leftJoin('components AS c', 'pc.id_component', '=', 'c.id')
                ->leftJoin('rona_awal AS ra', 'pra.id_rona_awal', '=', 'ra.id')
                ->where('impact_identifications.id_project', $request->id_project)
                ->whereNotNull('pc.id')
                ->whereNotNull('pra.id')
                ->orderBy('impact_identifications.id', 'asc')
                ->get();
            return ImpactIdentificationResource::collection($list);

            /*
            $list = ImpactIdentification::with('impactStudy')
                ->select('impact_identifications.*',
                'pc.id_project_stage',
                'c.id_project_stage AS id_project_stage_master',
                'c.name AS component_name_master',
                'pc.name AS component_name',
                'ra.name AS rona_awal_name_master',
                'pra.name AS rona_awal_name',
                'ct.name AS change_type_name')
                ->leftJoin('sub_project_components AS pc', 'impact_identifications.id_sub_project_component', '=', 'pc.id')
                ->leftJoin('sub_project_rona_awals AS pra', 'impact_identifications.id_sub_project_rona_awal', '=', 'pra.id')
                ->leftJoin('change_types AS ct', 'impact_identifications.id_change_type', '=', 'ct.id')
                ->leftJoin('components AS c', 'pc.id_component', '=', 'c.id')
                ->leftJoin('rona_awal AS ra', 'pra.id_rona_awal', '=', 'ra.id')
                ->where('impact_identifications.id_project', $request->id_project)
                ->whereNotNull('pc.id')
                ->whereNotNull('pra.id')
                ->orderBy('impact_identifications.id', 'asc')
                ->get();
            return ImpactIdentificationResource::collection($list);

            */
        }
        return ImpactIdentificationResource::collection($list);
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

    private function saveImpactStudies($params)
    {
        // save besran dampak
        DB::beginTransaction();
        $num_impacts = 0;
        $response = [];
        $idProject = 0;
        try {
            foreach ($params['study_data'] as $study) {
                if (array_key_exists('impacts', $study)) {
                    foreach ($study['impacts'] as $impact) {
                        if ($impact['id'] < 99999999) {
                            //not dummy
                            $num_impacts++;
                            $row = ImpactIdentification::find($impact['id']);
                            if ($row != null) {
                                if ($idProject == 0) {
                                    $idProject = $row->id_project;
                                }
                                $row->id_unit = $impact['id_unit'];
                                /*if(is_string($impact['id_change_type'])){
                                     $ctype = ChangeType::firstOrCreate(['name' => $impact['id_change_type']]);
                                     $row->id_change_type = $ctype->id;
                                 } else {
                                     $row->id_change_type = $impact['id_change_type'];
                                 }*/
                                if (($impact['id_change_type'] == 0) &&
                                    (($impact['change_type_name'] != null) &&
                                        (trim($impact['change_type_name']) != ""))
                                ) {
                                    $ctype = ChangeType::firstOrCreate(['name' => trim($impact['change_type_name'])]);
                                    $row->id_change_type = $ctype->id;
                                } else {
                                    $row->id_change_type = $impact['id_change_type'];
                                }
                                $row->nominal = $impact['nominal'];
                                $row->potential_impact_evaluation = $impact['potential_impact_evaluation'];
                                $row->is_hypothetical_significant = $impact['is_hypothetical_significant'];
                                $row->is_managed = $impact['is_managed'];
                                $row->initial_study_plan = $impact['initial_study_plan'];
                                $row->study_location = $impact['study_location'];
                                $row->study_length_year = $impact['study_length_year'];
                                $row->study_length_month = $impact['study_length_month'];
                                $row->save();
                                // save impact_study
                                $impact_study_saved = false;

                                if (isset($impact['impact_study'])) {
                                    $study = ImpactStudy::select('impact_studies.*')
                                        ->where('id_impact_identification', $impact['id'])
                                        ->first();
                                    if ($study != null) {
                                        $study->id_impact_identification = $impact['id'];
                                        $study->forecast_method = $impact['impact_study']['forecast_method'];
                                        $study->required_information = $impact['impact_study']['required_information'];
                                        $study->data_gathering_method = $impact['impact_study']['data_gathering_method'];
                                        $study->analysis_method = $impact['impact_study']['analysis_method'];
                                        $study->evaluation_method = $impact['impact_study']['evaluation_method'];
                                        $study->save();
                                        $impact_study_saved = true;
                                    } else {
                                        // create new
                                        if (ImpactStudy::create($impact['impact_study'])) {
                                            $impact_study_saved = true;
                                        }
                                    }
                                }

                                /** Potential Impact Evaluation */
                                if (isset($impact['potential_impact_evaluation'])) {
                                    foreach ($impact['potential_impact_evaluation'] as $pie) {
                                        $p = PotentialImpactEvaluation::where('id_impact_identification', $impact['id'])
                                            ->where('id_pie_param', $pie['id_pie_param'])->first();

                                        if (!$p) {
                                            $p = new PotentialImpactEvaluation();
                                            $p->id_impact_identification = $impact['id'];
                                            $p->id_pie_param = $pie['id_pie_param'];
                                        }
                                        $p->text = $pie['text'];
                                        $p->save();
                                    }
                                }

                                if ($impact_study_saved) {
                                    array_push($response, new ImpactIdentificationResource($row));
                                }
                            }
                        }
                    }
                } else {
                    if ($study['id'] < 99999999) {
                        $num_impacts++;
                        $row = ImpactStudy::select('impact_studies.*')
                            ->where('id_impact_identification', $study['id'])
                            ->first();
                        if ($row != null) {
                            $row->id_impact_identification = $study['id'];
                            $row->forecast_method = $study['impact_study']['forecast_method'];
                            $row->required_information = $study['impact_study']['required_information'];
                            $row->data_gathering_method = $study['impact_study']['data_gathering_method'];
                            $row->analysis_method = $study['impact_study']['analysis_method'];
                            $row->evaluation_method = $study['impact_study']['evaluation_method'];
                            $row->save();
                            $impact_study_saved = true;
                        } else {
                            // create new
                            if (ImpactStudy::create($study['impact_study'])) {
                                $impact_study_saved = true;
                            }
                        }

                        if ($impact_study_saved) {
                            array_push($response, new ImpactIdentificationResource($row));
                        }
                    }
                }
            }
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'code' => 500,
                'error' => $e->getMessage(),
            ]);
        }
        if (count($response) == $num_impacts) {
            DB::commit();
            // Workflow Amdal
            /*$project = Project::findOrFail($idProject);
            if ($project->marking == 'amdal.form-ka-drafting') {
                $project->workflow_apply('submit-amdal-form-ka');
                $project->save();
            }*/
            return response()->json([
                'status' => 200,
                'code' => 200,
                'data' => $response,
            ]);
        } else {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'code' => 500,
                'error' => 'Some rows failed to update.',
            ]);
        }
    }

    private function saveImpactUnits($params)
    {
        DB::beginTransaction();
        $errors = [];
        $updated = 0;
        $count = 0;
        $response = [];
        $unitEmpty = 0;
        $idProject = 0;
        try {
            foreach ($params['unit_data'] as $impact) {
                if (!$impact['is_stage']) {
                    $count++;
                    $toUpdate = ImpactIdentification::find($impact['id']);
                    if ($toUpdate != null) {
                        if ($idProject == 0) {
                            $idProject = $toUpdate->id_project;
                        }
                        if (empty($toUpdate->unit)) {
                            $unitEmpty++;
                        }
                        try {
                            $empty = 0;
                            if (empty($impact['id_change_type']) && empty($impact['change_type_name']) && !$params['temporary']) {
                                array_push($errors, 'Perubahan wajib diisi.');
                                $empty++;
                            }
                            if (empty($impact['unit']) && !$params['temporary']) {
                                array_push($errors, 'Besaran wajib diisi.');
                                $empty++;
                            }
                            if ($empty > 0) {
                                continue;
                            }
                            if (!empty($impact['id_change_type'])) {
                                $impact['change_type_name'] = null;
                            }
                            $toUpdate->id_change_type = $impact['id_change_type'];
                            $toUpdate->change_type_name = $impact['change_type_name'];
                            $toUpdate->unit = $impact['unit'];
                            $toUpdate->save();
                            $updated++;
                            array_push($response, $toUpdate);
                        } catch (Exception $e) {
                            array_push($errors, 'Gagal menyimpan Dampak. +'. $e->getMessage());
                        }
                    }
                }
            }
            if ($updated == $count) {
                SaveStatus::updateOrCreate(
                    [
                        'project_id' => $idProject,
                        'section'    => 'formulir.uklupl.jenis.besaran.dampak'
                    ],
                    [
                        'temporary'  => $params['temporary'],
                    ]
                );

                if ($unitEmpty == $count) {
                    // First time saving unit: trigger workflow
                    /*$project = Project::findOrFail($idProject);
                    if ($project->marking == 'announcement-completed') {
                        $project->workflow_apply('fill-uklupl-form');
                        $project->save();
                    }*/
                }
                DB::commit();
                return response()->json([
                    'status' => 200,
                    'code' => 200,
                    'data' => $response,
                ], 200);
            } else {
                DB::rollBack();
                return response()->json([
                    'status' => 500,
                    'code' => 500,
                    'message' => implode(', ', $errors),
                ], 500);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'code' => 500,
                'message' => implode(', ', $errors),
            ], 500);
        }
    }

    private function saveMatriks($params)
    {
        $is_save_dph = isset($params['save_dph']) && $params['save_dph'];
        // save matriks identifikasi dampak
        $checked = 0;
        $inserted = 0;
        DB::beginTransaction();
        //clear items
        if ($is_save_dph) {
            $impacts = ImpactIdentification::where('id_project', $params['id_project'])->get();
            foreach ($impacts as $impact) {
                $impact->is_hypothetical_significant = false;
                $impact->save();
            }
        } else {
            ImpactIdentification::where('id_project', $params['id_project'])->delete();
        }
        //insert checked items
        try {
            foreach ($params['checked'] as $item) {
                if ($item['id'] < 99999999) {
                    foreach ($item['sub'] as $sub) {
                        if ($sub['checked']) {
                            if ($is_save_dph) {
                                $impact = ImpactIdentification::select('*')
                                    ->where('id_sub_project_rona_awal', $item['id'])
                                    ->where('id_sub_project_component', $sub['id'])
                                    ->first();
                                if ($impact != null) {
                                    $impact->is_hypothetical_significant = true;
                                    $impact->save();
                                }
                            } else {
                                $created = ImpactIdentification::create([
                                    'id_project' => $params['id_project'],
                                    'id_sub_project_rona_awal' => $item['id'],
                                    'id_sub_project_component' => $sub['id'],
                                ]);
                                if ($created) {
                                    $inserted++;
                                }
                                $checked++;
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            return response()->json(['code' => 500, 'error' => $e->getMessage()]);
        }
        if ($is_save_dph) {
            DB::commit();
            return response()->json(['code' => 200]);
        } else {
            if ($inserted == $checked) {
                DB::commit();
                return response()->json(['code' => 200]);
            } else {
                DB::rollBack();
                return response()->json(['code' => 500]);
            }
        }
    }

    private function saveMatriksUkl($params)
    {
        $updated = 0;
        $errors = [];
        $count = 0;
        $response = [];
        $idProject = 0;
        DB::beginTransaction();

        try {
            foreach ($params['env_manage_plan_data'] as $impact) {
                if (!$impact['is_stage']) {
                    $impactObj = ImpactIdentification::findOrFail($impact['id']);
                    if ($idProject == 0) {
                        $idProject = $impactObj->id_project;
                    }

                    $env_manage_plan = null;
                    if ($impact['env_manage_plan']['id']) {
                        $env_manage_plan = EnvManagePlan::findOrFail($impact['env_manage_plan']['id']);
                    } else {
                        $env_manage_plan = new EnvManagePlan();
                        $env_manage_plan->id_impact_identifications = $impact['id'];
                    }

                    if (is_numeric($impact['env_manage_plan']['period_number'])) {
                        $env_manage_plan->period = $impact['env_manage_plan']['period_number'] . '-' . $impact['env_manage_plan']['period_description'];
                    }

                    $env_manage_plan->executor = $impact['env_manage_plan']['executor'];
                    $env_manage_plan->supervisor = $impact['env_manage_plan']['supervisor'];
                    $env_manage_plan->report_recipient = $impact['env_manage_plan']['report_recipient'];
                    $env_manage_plan->description = $impact['env_manage_plan']['description'];
                    $env_manage_plan->save();

                    $forms = $impact['env_manage_plan']['forms'];
                    if (count($forms)) {
                        for ($i = 0; $i < count($forms); $i++) {
                            $form_manage = null;
                            if ($forms[$i]['id']) {
                                $form_manage = EnvPlanForm::findOrFail($forms[$i]['id']);
                            } else {
                                $form_manage = new EnvPlanForm();
                                $form_manage->id_env_manage_plan = $env_manage_plan->id;
                            }

                            $form_manage->description = $forms[$i]['description'];
                            $form_manage->save();
                        }
                    }

                    $locations = $impact['env_manage_plan']['locations'];
                    if (count($locations)) {
                        for ($i = 0; $i < count($locations); $i++) {
                            $location_manage = null;
                            if ($locations[$i]['id']) {
                                $location_manage = EnvPlanLocation::findOrFail($locations[$i]['id']);
                            } else {
                                $location_manage = new EnvPlanLocation();
                                $location_manage->id_env_manage_plan = $env_manage_plan->id;
                            }

                            $location_manage->description = $locations[$i]['description'];
                            $location_manage->save();
                        }
                    }

                    // if ($impact['env_manage_plan'] != null) {
                    //     foreach ($impact['env_manage_plan'] as $plan) {
                    //         $count++;
                    //         $toUpdate = EnvManagePlan::find($plan['id']);
                    //         if ($toUpdate != null) {
                    //             $toUpdate->form = $plan['form'];
                    //             $toUpdate->location = $plan['location'];
                    //             if (is_numeric($plan['period_number'])) {
                    //                 $toUpdate->period = $plan['period_number'] . '-' . $plan['period_description'];
                    //             }
                    //             $toUpdate->executor = $plan['executor'];
                    //             $toUpdate->supervisor = $plan['supervisor'];
                    //             $toUpdate->report_recipient = $plan['report_recipient'];
                    //             $toUpdate->description = $plan['description'];
                    //             try {
                    //                 $toUpdate->save();
                    //                 $updated++;
                    //                 array_push($response, $toUpdate);
                    //             } catch (Exception $e) {
                    //                 array_push($errors, 'Gagal menyimpan bentuk kelola \'' . $plan['form'] . '\'');
                    //             }
                    //         }
                    //     }
                    // }
                }
            }

            // DELETED FORMS
            $deleted_forms = json_decode($params['deleted_form'], true);
            if (count($deleted_forms) > 0) {
                EnvPlanForm::whereIn('id', $deleted_forms)->delete();
            }

            // DELETED LOCATIONS
            $deleted_locations = json_decode($params['deleted_location'], true);
            if (count($deleted_locations) > 0) {
                EnvPlanLocation::whereIn('id', $deleted_locations)->delete();
            }

            SaveStatus::updateOrCreate(
                [
                    'project_id' => $idProject,
                    'section'    => 'matriks.ukl'
                ],
                [
                    'temporary'  => $params['temporary'],
                ]
            );

            // save workflow when not temporary
            if (!$params['temporary']) {
                $project = Project::findOrFail($idProject);
                if ($project->marking == 'uklupl-mt.form') {
                    $project->workflow_apply('fill-uklupl-matrix-ukl');
                    $project->save();
                }
            }

            DB::commit();
            return response()->json([
                'status' => 200,
                'code' => 200,
                'data' => $response,
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function saveMatriksUpl($params)
    {
        $updated = 0;
        $errors = [];
        $count = 0;
        $response = [];
        $idProject = 0;
        DB::beginTransaction();

        try {
            foreach ($params['env_monitor_plan_data'] as $impact) {
                if (!$impact['is_stage']) {
                    $impactObj = ImpactIdentification::findOrFail($impact['id']);
                    if ($idProject == 0) {
                        $idProject = $impactObj->id_project;
                    }

                    $env_monitor_plan = null;
                    if ($impact['env_monitor_plan']['id']) {
                        $env_monitor_plan = EnvMonitorPlan::findOrFail($impact['env_monitor_plan']['id']);
                    } else {
                        $env_monitor_plan = new EnvMonitorPlan();
                        $env_monitor_plan->id_impact_identifications = $impact['id'];
                    }

                    if (is_numeric($impact['env_monitor_plan']['period_number'])) {
                        $env_monitor_plan->period = $impact['env_monitor_plan']['period_number'] . '-' . $impact['env_monitor_plan']['period_description'];
                    }

                    $env_monitor_plan->executor = $impact['env_monitor_plan']['executor'];
                    $env_monitor_plan->supervisor = $impact['env_monitor_plan']['supervisor'];
                    $env_monitor_plan->report_recipient = $impact['env_monitor_plan']['report_recipient'];
                    $env_monitor_plan->description = $impact['env_monitor_plan']['description'];
                    $env_monitor_plan->save();

                    $forms = $impact['env_monitor_plan']['forms'];
                    if (count($forms)) {
                        for ($i = 0; $i < count($forms); $i++) {
                            $form_manage = null;
                            if ($forms[$i]['id']) {
                                $form_manage = EnvPlanForm::findOrFail($forms[$i]['id']);
                            } else {
                                $form_manage = new EnvPlanForm();
                                $form_manage->id_env_monitor_plan = $env_monitor_plan->id;
                            }

                            $form_manage->description = $forms[$i]['description'];
                            $form_manage->save();
                        }
                    }

                    $locations = $impact['env_monitor_plan']['locations'];
                    if (count($locations)) {
                        for ($i = 0; $i < count($locations); $i++) {
                            $location_manage = null;
                            if ($locations[$i]['id']) {
                                $location_manage = EnvPlanLocation::findOrFail($locations[$i]['id']);
                            } else {
                                $location_manage = new EnvPlanLocation();
                                $location_manage->id_env_monitor_plan = $env_monitor_plan->id;
                            }

                            $location_manage->description = $locations[$i]['description'];
                            $location_manage->save();
                        }
                    }

                    // if ($impact['env_monitor_plan'] != null) {
                    //     foreach ($impact['env_monitor_plan'] as $plan) {
                    //         $count++;
                    //         $toUpdate = EnvMonitorPlan::find($plan['id']);
                    //         if ($toUpdate != null) {
                    //             $toUpdate->form = $plan['form'];
                    //             $toUpdate->location = $plan['location'];
                    //             if (is_numeric($plan['period_number'])) {
                    //                 $toUpdate->period = $plan['period_number'] . '-' . $plan['period_description'];
                    //             }
                    //             $toUpdate->executor = $plan['executor'];
                    //             $toUpdate->supervisor = $plan['supervisor'];
                    //             $toUpdate->report_recipient = $plan['report_recipient'];
                    //             $toUpdate->description = $plan['description'];
                    //             try {
                    //                 $toUpdate->save();
                    //                 $updated++;
                    //                 array_push($response, $toUpdate);
                    //             } catch (Exception $e) {
                    //                 array_push($errors, 'Gagal menyimpan bentuk pemantauan \'' . $plan['form'] . '\'');
                    //             }
                    //         }
                    //     }
                    // }
                }
            }

            // DELETED FORMS
            $deleted_forms = json_decode($params['deleted_form'], true);
            if (count($deleted_forms) > 0) {
                EnvPlanForm::whereIn('id', $deleted_forms)->delete();
            }

            // DELETED LOCATIONS
            $deleted_locations = json_decode($params['deleted_location'], true);
            if (count($deleted_locations) > 0) {
                EnvPlanLocation::whereIn('id', $deleted_locations)->delete();
            }

            SaveStatus::updateOrCreate(
                [
                    'project_id' => $idProject,
                    'section'    => 'matriks.upl'
                ],
                [
                    'temporary'  => $params['temporary'],
                ]
            );

            // save workflow when not temporary
            if (!$params['temporary']) {
                $project = Project::findOrFail($idProject);
                if ($project->marking == 'uklupl-mt.matrix-ukl') {
                    $project->workflow_apply('fill-uklupl-matrix-upl');
                    $project->save();
                }
            }
            DB::commit();
            return response()->json([
                'status' => 200,
                'code' => 200,
                'data' => $response,
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $params = $request->all();
        /*if(isset($params['scoping']) && ($params['scoping'])){
            $validator = $request->validate([
                'id_project' => 'required',
                'id_project_component' => 'required',
                'id_project_rona_awal' => 'required',
            ]);

            $impact = ImpactIdentification::firstOrCreate($validator);
            if(!$impact) return response(500);

            // pie A,
            $pComponent = ProjectComponent::where('id', $request['id_project_component'])->first();
            $pHue = ProjectRonaAwal::where('id', $request['id_project_rona_awal'])->first();
            if ($pComponent && $pHue){
                $text = "<p><strong>Deskripsi</strong></p>";
                $text .= "<p>".$pComponent->description."</p>";
                $text .= "<p><strong>Besaran</strong></p>";
                $text .= "<p>".$pComponent->unit."</p>";



                $spComponents = SubProjectComponent::select(
                    'sub_projects_components.id', 'sub_projects_components.description_specific', 'sub_projects_components.unit' )
                    ->join('sub_projects', 'sub_projects.id', '=','sub_projects_components.id')
                    ->where('sub_projects_components.id_component', $request[''])

                $pie_A = PotentialImpactEvaluation::create([
                    'id_impact_identification' => $created->id,
                    'id_pie_param' => 1,
                    'text' => $text
                ]);


                $pie_B = PotentialImpactEvaluation::create([
                    'id_impact_identification' => $created->id,
                    'id_pie_param' => 2,
                ]);
                $pie_C = PotentialImpactEvaluation::create([
                    'id_impact_identification' => $created->id,
                    'id_pie_param' => 3,
                ]);
                $pie_D = PotentialImpactEvaluation::create([
                    'id_impact_identification' => $created->id,
                    'id_pie_param' => 4,
                ]);

                $text = "<p><strong>Deskripsi</strong></p>";
                $text .= "<p>".$subProjHue->description_specific."</p>";
                $text .= "<p><strong>Besaran</strong></p>";
                $text .= "<p>".$subProjHue->unit."</p>";
                $pie_E = PotentialImpactEvaluation::create([
                    'id_impact_identification' => $created->id,
                    'id_pie_param' => 5,
                    'text' => $text
                ]);

            return response(200);
        }*/
        if (isset($params['checked']) && isset($params['id_project'])) {
            return $this->saveMatriks($params);
        } else if (isset($params['study_data'])) {
            return $this->saveImpactStudies($params);
        } else if (isset($params['unit_data'])) {
            return $this->saveImpactUnits($params);
        } else if (isset($params['env_manage_plan_data'])) {
            return $this->saveMatriksUkl($params);
        } else if (isset($params['env_monitor_plan_data'])) {
            return $this->saveMatriksUpl($params);
        } else if (
            isset($params['id_project']) && isset($params['id_sub_project_component'])
            && isset($params['id_sub_project_rona_awal'])
        ) {
            $validator = $request->validate([
                'id_project' => 'required',
                'id_sub_project_component' => 'required',
                'id_sub_project_rona_awal' => 'required',
            ]);
            DB::beginTransaction();
            $created = ImpactIdentification::create($validator);
            if ($created) {
                DB::commit();
                // init pies //proe
                // A
                $subProj = SubProjectComponent::where('id', $request['id_sub_project_component'])->first();
                $subProjHue = SubProjectRonaAwal::where('id', $request['id_sub_project_rona_awal'])->first();
                if ($subProj && $subProjHue) {
                    $text = "<p><strong>Deskripsi</strong></p>";
                    $text .= "<p>" . $subProj->description_specific . "</p>";
                    $text .= "<p><strong>Besaran</strong></p>";
                    $text .= "<p>" . $subProj->unit . "</p>";

                    $pie_A = PotentialImpactEvaluation::create([
                        'id_impact_identification' => $created->id,
                        'id_pie_param' => 1,
                        'text' => $text
                    ]);


                    $pie_B = PotentialImpactEvaluation::create([
                        'id_impact_identification' => $created->id,
                        'id_pie_param' => 2,
                    ]);
                    $pie_C = PotentialImpactEvaluation::create([
                        'id_impact_identification' => $created->id,
                        'id_pie_param' => 3,
                    ]);
                    $pie_D = PotentialImpactEvaluation::create([
                        'id_impact_identification' => $created->id,
                        'id_pie_param' => 4,
                    ]);

                    $text = "<p><strong>Deskripsi</strong></p>";
                    $text .= "<p>" . $subProjHue->description_specific . "</p>";
                    $text .= "<p><strong>Besaran</strong></p>";
                    $text .= "<p>" . $subProjHue->unit . "</p>";
                    $pie_E = PotentialImpactEvaluation::create([
                        'id_impact_identification' => $created->id,
                        'id_pie_param' => 5,
                        'text' => $text
                    ]);
                }
                return $created;
            } else {
                DB::rollBack();
            }
        } else {
            $validator = $request->validate([
                'id_project' => 'required',
                'id_component' => 'required',
                'id_rona_awal' => 'required',
            ]);
            DB::beginTransaction();
            $created = ImpactIdentification::create($validator);
            if ($created) {
                DB::commit();
            } else {
                DB::rollBack();
            }
            return new ImpactIdentificationResource($created);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Entity\ImpactIdentification  $impactIdentification
     * @return \Illuminate\Http\Response
     */
    public function show(ImpactIdentification $impactIdentification)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Entity\ImpactIdentification  $impactIdentification
     * @return \Illuminate\Http\Response
     */
    public function edit(ImpactIdentification $impactIdentification)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Entity\ImpactIdentification  $impactIdentification
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ImpactIdentification $impactIdentification)
    {
        //
    }

    /**
     * Get PIE entries
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function pieEntries(Request $request)
    {
        if (!$request->id_impact_identification) return response(array(), 200);
        $classes = ['App\Entity\PotentialImpactEvaluation', 'App\Entity\PotentialImpactEvalClone'];
        $id_names = ['id_impact_identification', 'id_impact_identification_clone'];
        $pies = $classes[$request->mode]::whereIn($id_names[$request->mode], $request->id_impact_identification)
            ->orderBy($id_names[$request->mode], 'ASC')
            ->orderBy('id_pie_param', 'ASC')
            ->get();
        // ->where('id_pie_param' , $request->id_pie_param)->all();
        return response($pies);
    }

    /**
     * Accomodating DPDPH in  master-detail format
     */

    /**
     * Get Impact Identifications
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getImpacts(Request $request)
    {
        /* $impactIdClasses = [
            'ImpactIdentification' => 'impact_identifications',
            'ImpactIdentificationClone' => 'impact_identification_clones'
        ]; */

        $impactClasses = ['App\Entity\ImpactIdentification', 'App\Entity\ImpactIdentificationClone'];
        $tables = ['impact_identifications', 'impact_identification_clones'];
        $ctStatement = ['COALESCE(ii.change_type_name, ct."name")', 'ct."name"'];
        $comments = ['dpdph-ka', 'dpdph-andal'];
        $impacts = $impactClasses[$request->mode]::from($tables[$request->mode] . ' AS ii')
            ->selectRaw('ii.id, ii.id_change_type,
            ' . $ctStatement[$request->mode] . ' as change_type_name,
            -- COALESCE(c.id_project_stage, spc.id_project_stage) as project_stage,
            c.id_project_stage as project_stage,
            ps.name as stage,
            -- COALESCE(c."name", spc."name") as komponen,
            c."name" as komponen,
            -- COALESCE(ra."name", spra."name") as rona_awal,
            ra."name" as rona_awal,
            ii.initial_study_plan,
            ii.is_hypothetical_significant,
            ii.is_managed,
            ii.study_length_month,
            ii.study_length_year,
            ii.study_location,
            -- array_agg(row_to_json(sp)) as sub_projects,
            -- string_agg(sp.name, \', \') as sub_projects,
            count(cm.id) as comment
            -- sp."name" as kegiatan,
            -- sp.type
        ')

            // ->leftJoin('sub_project_rona_awals AS spra', 'pra.id_rona_awal', '=', 'spra.id_rona_awal')
            //->leftJoin('sub_project_components AS spc', 'ii.id_sub_project_component', '=', 'spc.id')
            ->join('project_rona_awals AS pra', 'ii.id_project_rona_awal', '=', 'pra.id')
            ->join('project_components AS pc', 'ii.id_project_component', '=', 'pc.id')
            // ->join('projects', 'projects.id', '=', 'spc.id_project')
            //->leftjoin('sub_projects as sp', 'sp.id', '=', 'spc.id_sub_project')
            ->leftJoin('change_types AS ct', 'ii.id_change_type', '=', 'ct.id')
            ->leftJoin('components AS c', 'pc.id_component', '=', 'c.id')
            ->leftJoin('rona_awal AS ra', 'pra.id_rona_awal', '=', 'ra.id')
            ->leftJoin('comments as cm', function ($join) use ($request, $comments) {
                $join->on('cm.id_impact_identification', '=', 'ii.id')
                    ->on('cm.document_type', '=', DB::raw('\'' . $comments[$request->mode] . '\''));
            })
            //->join('project_stages as ps', 'ps.id', '=', DB::raw('COALESCE(c.id_project_stage, spc.id_project_stage)'));
            ->join('project_stages as ps', 'ps.id', '=', 'c.id_project_stage');

        if ($request->id_project) {
            /**
             */
            return response(
                $impacts
                    ->where('ii.id_project', $request->id_project)
                    ->where(function ($query) {
                        $query->whereNotNull('ra.id');
                        $query->orWhereNotNull('pra.id');
                        return $query;
                    })
                    /*->where(function($query) {
                $query->whereNotNull('c.id');
                $query->orWhereNotNull('spc.id');
                return $query;
            })*/
                    ->groupBy(
                        'ii.id',
                        'ct.name',
                        'c.id_project_stage',
                        /*'spc.id_project_stage',*/
                        'ps.name',
                        'c.name', /*'spc.name',*/
                        'ra.name'
                    ) /*, 'spra.name', 'sp.name', 'sp.type')*/
                    ->orderBy('ii.id', 'asc')
                    ->get()
            );
            //return response($impacts);
        }
        if ($request->id) {
            $res = $impacts->where('ii.id', $request->id)
                ->groupBy(
                    'ii.id',
                    'ct.name',
                    'c.id_project_stage',
                    /*'spc.id_project_stage',*/
                    'ps.name',
                    'c.name', /*'spc.name',*/
                    'ra.name'
                ) //, 'spra.name', 'sp.name', 'sp.type')
                ->first();
            if ($res) return response($res);
            return response('Dampak tidak ditemukan', 404);
        }

        return response('Data tidak ditemukan', 418);
    }

    /**
     * Save impacts
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function saveImpacts(Request $request)
    {
        $request = $request->all();
        if (empty($request)) {
            return response('empty request', 418);
        }

        $impactClasses = ['App\Entity\ImpactIdentification', 'App\Entity\ImpactIdentificationClone'];
        $pieClasses = ['App\Entity\PotentialImpactEvaluation', 'App\Entity\PotentialImpactEvalClone'];
        $pieIdNames = ['id_impact_identification', 'id_impact_identification_clone'];

        $pieIdName = $pieIdNames[$request['mode']];

        $saved = [];
        foreach ($request['data'] as $imp) {
            $id = $imp['id'];
            $impact = $impactClasses[$request['mode']]::find($id);
            if ($impact) {
                $impact->is_hypothetical_significant = $imp['is_hypothetical_significant'];
                $impact->initial_study_plan = $imp['initial_study_plan'];
                if ($imp['is_hypothetical_significant'] == true) {
                    $impact->study_length_month = $imp['study_length_month'];
                    $impact->study_length_year = $imp['study_length_year'];
                    $impact->study_location = $imp['study_location'];
                    $impact->is_managed = false;
                } else {
                    $impact->study_length_month =  null;
                    $impact->study_length_year = null;
                    $impact->study_location = null;
                    $impact->is_managed = ($imp['is_hypothetical_significant'] == false) ? $imp['is_managed'] : false;
                }

                if (($imp['id_change_type'] == 0) &&
                    (($imp['change_type_name']  != null) &&
                        (trim($imp['change_type_name']) != ""))
                ) {
                    $ctype = ChangeType::firstOrCreate(['name' => trim($imp['change_type_name'])]);
                    $impact->id_change_type = $ctype->id;
                } else {
                    $impact->id_change_type = $imp['id_change_type'];
                }
                $impact->save();
                array_push($saved, $impact);

                if (isset($imp['pie']) && (!empty($imp['pie']))) {

                    foreach ($imp['pie'] as $pie) {
                        $p = $pieClasses[$request['mode']]::where($pieIdNames[$request['mode']], $imp['id'])
                            ->where('id_pie_param', $pie['id_pie_param'])->first();
                        if (empty($p)) {
                            $p = new $pieClasses[$request['mode']]();
                            $p->$pieIdName = $imp['id'];
                            $p->id_pie_param = $pie['id_pie_param'];
                        }
                        $p->text = $pie['text'];
                        $p->save();
                    }
                }
                if (isset($imp['activities'])) {
                    if (empty($imp['activities'])) {
                        $res = ImpactKegiatanLainSekitar::where([
                            'id_impact_identification' => $imp['id'],
                            'is_andal' => $request['mode']
                        ])->delete();
                    } else {
                        $ids = [];
                        foreach ($imp['activities'] as $act) {
                            $co = ImpactKegiatanLainSekitar::firstOrCreate([
                                'id_impact_identification' => $imp['id'],
                                'id_project_kegiatan_lain_sekitar' => $act['id_project_kegiatan_lain_sekitar'],
                                'is_andal' => $request['mode']
                            ]);
                            $ids[] = $co->id;
                        }
                        $res = ImpactKegiatanLainSekitar::where('id_impact_identification', $imp['id'])
                            ->whereNotIn('id', $ids)->delete();
                    }
                }
            }
        }

        return response($saved, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Entity\ImpactIdentification  $impactIdentification
     * @return \Illuminate\Http\Response
     */
    public function destroy(ImpactIdentification $impactIdentification)
    {
        //
    }
}
