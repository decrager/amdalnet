<?php

namespace App\Http\Controllers;

use App\Entity\Project;
use App\Entity\SupportDoc;
use App\Http\Resources\ProjectResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        //validate request

        $validator = Validator::make(
            $request->all(),
            [
                'biz_type'=> 'required',
                'project_title'=> 'required',
                'scale'=> 'required',
                'scale_unit'=> 'required',
                'project_type'=> 'required',
                'sector'=> 'required',
                'description'=> 'required',
                'id_applicant'=> 'required',
                'id_prov'=> 'required',
                'id_district'=> 'required',
                'location'=> 'required',
                'field'=> 'required',
                'location_desc'=> 'required',
                'risk_level'=> 'required',
                'project_year'=> 'required',
                'id_drafting_team'=> 'required',
                'kbli'=> 'required',
                'result_risk'=> 'required',
                'required_doc'=> 'required',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 403);
        } else {
            $params = $request->all();

            //create file map
            $file = $request->file('fileMap');
            $name = 'project/' . uniqid() . '.' . $file->extension();
            $file->storePubliclyAs('public', $name);

            //create lpjp
            $project = Project::create([
                'biz_type'=> $params['biz_type'],
                'project_title'=> $params['project_title'],
                'scale'=> $params['scale'],
                'scale_unit'=> $params['scale_unit'],
                'project_type'=> $params['project_type'],
                'sector'=> $params['sector'],
                'description'=> $params['description'],
                'id_applicant'=> $params['id_applicant'],
                'id_prov'=> $params['id_prov'],
                'id_district'=> $params['id_district'],
                'address'=> $params['location'],
                'field'=> $params['field'],
                'location_desc'=> $params['location_desc'],
                'risk_level'=> $params['risk_level'],
                'project_year'=> $params['project_year'],
                'id_formulator_team'=> $params['id_drafting_team'],
                'kbli'=> $params['kbli'],
                'result_risk'=> $params['result_risk'],
                'required_doc'=> $params['required_doc'],
                'map' => Storage::url($name),
            ]);

            //save supports documents
            // foreach ($request->listSupportDoc as $sup) {
            //     SupportDoc::create([
            //         'name' => $sup['']
            //     ]);
            // }
            
            return new ProjectResource($project);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Entity\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function show(Project $project)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Entity\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function edit(Project $project)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Entity\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Project $project)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Entity\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function destroy(Project $project)
    {
        //
    }
}
