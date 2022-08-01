<?php

namespace App\Http\Controllers;

use App\Entity\EnvManageDoc;
use App\Entity\ImpactIdentification;
use App\Entity\Project;
use App\Entity\ProjectStage;
use Illuminate\Http\Request;

class MatriksUklUplController extends Controller
{
    public function getTableUkl(Request $request, $id)
    {
        return $this->getTableData($request, $id, 'ukl');
    }

    public function getTableUpl(Request $request, $id)
    {
        return $this->getTableData($request, $id, 'upl');
    }

    public function getIsFormComplete(Request $request, $id)
    {
        // check if at least one UKL & UPL per impact_idt is filled
        $impacts = ImpactIdentification::select('*')
            ->with('envManagePlan')
            ->where('id_project', $id)
            ->get();
        $uklCount = 0;
        foreach ($impacts as $impact) {
            if ($impact->envManagePlan) {
                $uklCount++;
            }
        }
        // $uklFilled = $uklCount == count($impacts);
        $uklFilled = $uklCount > 0;
        $uplCount = 0;
        foreach ($impacts as $impact) {
            if ($impact->envMonitorPlan) {
                $uplCount++;
            }
        }
        // $uplFilled = $uplCount == count($impacts);
        $uplFilled = $uplCount > 0;
        // check if env_manage_docs are uploaded
        $docs = EnvManageDoc::select('*')
            ->where('id_project', $id)
            ->orderBy('type', 'desc')
            ->get();
        $sppl = false;
        $dpt = false;
        foreach ($docs as $doc) {
            if ($doc->type == 'SPPL' && !empty($doc->filepath)){
                $sppl = true;
            }
            if ($doc->type == 'DPT' && !empty($doc->filepath)){
                $dpt = true;
            }
        }
        $docsUploaded = $sppl && $dpt;
        return response()->json([
            'status' => 200,
            'code' => 200,
            'data' => $uklFilled && $uplFilled && $docsUploaded,
        ], 200);
    }

    public function getProjectMarking(Request $request, $id)
    {
        $project = Project::find($id);
        if ($project) {
            return response()->json([
                'status' => 200,
                'code' => 200,
                'data' => $project->marking,
            ], 200);
        }
        return response()->json([
            'status' => 404,
            'code' => 404,
            'message' => 'Project not found',
        ], 404);
    }

    private function setEnvPlanData($envPlan) {
        $split_period = explode('-', $envPlan->period);
        $envPlan->period_number = null;
        $envPlan->period_description = null;
        $envPlan->is_selected = true;
        $envPlan->errors = [];
        if (is_numeric($split_period[0])) {
            $envPlan->period_number = $split_period[0];
        }
        if (count($split_period) > 1) {
            $envPlan->period_description = $split_period[1];
        }
        return $envPlan;
    }

    private function getTableData(Request $request, $id, $type)
    {
        if ($type != 'ukl' && $type != 'upl'){
            return response()->json([
                'status' => 500,
                'code' => 500,
                'message' => 'Tipe matriks tidak valid.',
            ], 500);
        }
        $with = '';
        if ($type == 'ukl'){
            $with = ['envManagePlan' => function($q) {
                $q->with(['forms', 'locations']);
            }];
        } else if ($type == 'upl'){
            $with = ['envMonitorPlan' => function($q) {
                $q->with(['forms', 'locations']);
            }];
        }
        /*
            // commented by HH to comply with pelingkupan ver 20220322
            $impacts = ImpactIdentification::from('impact_identifications AS ii')
            ->with($with)
            ->selectRaw('ii.id, ii.id_change_type, ii.change_type_name,
                ct."name" as change_type_name_master,
                spc.id_project_stage,
                c.id_project_stage as id_project_stage_master,
                spc."name" as component_name,
                c."name" as component_name_master,
                spra."name" as rona_awal_name,
                ra."name" as rona_awal_name_master,
                ii."unit",
                u."name" as unit_master,
                ii.nominal')
            ->leftJoin('change_types AS ct', 'ii.id_change_type', '=', 'ct.id')
            ->leftJoin('sub_project_rona_awals AS spra', 'ii.id_sub_project_rona_awal', '=', 'spra.id')
            ->leftJoin('sub_project_components AS spc', 'ii.id_sub_project_component', '=', 'spc.id')
            ->leftJoin('components AS c', 'spc.id_component', '=', 'c.id')
            ->leftJoin('rona_awal AS ra', 'spra.id_rona_awal', '=', 'ra.id')
            ->leftJoin('units AS u', 'ii.id_unit', '=', 'u.id')
            ->where('ii.id_project', $id)
            ->orderBy('ii.id', 'asc')
            ->get();*/

            $impacts = ImpactIdentification::from('impact_identifications AS ii')
            ->with($with)
            ->selectRaw('ii.id, ii.id_change_type, ii.change_type_name,
                ct."name" as change_type_name_master,
                c.id_project_stage,
                c.id_project_stage as id_project_stage_master,
                c."name" as component_name,
                c."name" as component_name_master,
                ra."name" as rona_awal_name,
                ra."name" as rona_awal_name_master,
                ii."unit",
                u."name" as unit_master,
                ii.nominal')
            ->leftJoin('change_types AS ct', 'ii.id_change_type', '=', 'ct.id')
            ->leftJoin('project_rona_awals AS spra', 'ii.id_project_rona_awal', '=', 'spra.id')
            ->leftJoin('project_components AS spc', 'ii.id_project_component', '=', 'spc.id')
            ->leftJoin('components AS c', 'spc.id_component', '=', 'c.id')
            ->leftJoin('rona_awal AS ra', 'spra.id_rona_awal', '=', 'ra.id')
            ->leftJoin('units AS u', 'ii.id_unit', '=', 'u.id')
            ->where('ii.id_project', $id)
            ->orderBy('ii.id', 'asc')
            ->get();

        $ids = [4,1,2,3];
        $stages = ProjectStage::select('id', 'name')->get()->sortBy(function($model) use($ids) {
            return array_search($model->getKey(),$ids);
        });
        $data = [];
        $index = 1;
        foreach ($stages as $stage) {
            $index = 1;
            $item = [];
            $item['is_stage'] = true;
            $item['project_stage_name'] = $stage->name;
            array_push($data, $item);
            foreach ($impacts as $impact) {
                $impact->new_form = null;
                $impact->new_location = null;
                if (empty($impact->id_project_stage)) {
                    $impact->id_project_stage = $impact->id_project_stage_master;
                }
                if (empty($impact->component_name)) {
                    $impact->component_name = $impact->component_name_master;
                }
                if (empty($impact->rona_awal_name)) {
                    $impact->rona_awal_name = $impact->rona_awal_name_master;
                }
                if (empty($impact->unit)) {
                    $impact->unit = $impact->nominal . ' ' . $impact->unit_master;
                }
                if (!empty($impact->id_change_type)) {
                    $impact->change_type_name = $impact->change_type_name_master;
                }
                if ($impact->id_project_stage == $stage->id) {
                    $impact['is_stage'] = false;
                    $impact['index'] = $index;
                    if ($type == 'ukl'){
                        $impact->env_manage_plan = $impact->envManagePlan ? $this->setEnvPlanData($impact->envManagePlan) : null;
                    } else if ($type == 'upl'){
                        $impact->env_monitor_plan = $impact->envMonitorPlan ? $this->setEnvPlanData($impact->envMonitorPlan) : null;
                    }
                    array_push($data, $impact);
                    $index++;
                }
            }
        }
        return $data;
    }
}
