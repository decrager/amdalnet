<?php

namespace App\Http\Controllers;

use App\Entity\ProjectMapAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\ProjectMapAttachmentResource;
use phpDocumentor\Reflection\PseudoTypes\LowercaseString;

class ProjectMapAttachmentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $files = ProjectMapAttachment::all();
        if ($request->id_project) {
            $files = $files->where('id_project', $request->id_project);
            return ProjectMapAttachmentResource::collection($files);
        }
        return ProjectMapAttachmentResource::collection($files);
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
        if ($request['files']) {
            $id_project = $request['id_project'];
            $params = $request['params'];
            foreach ($request->file('files') as $i => $file) {
                $temp = json_decode($params[$i]);
                $map = ProjectMapAttachment::firstOrNew([
                    'id_project' => $id_project,
                    'attachment_type' => $temp->attachment_type,
                    'file_type' => $temp->file_type
                ]);

                if ($map->id) {
                    // unlink old files
                    if (file_exists(storage_path() . DIRECTORY_SEPARATOR . $map->stored_filename)) {
                        unlink(storage_path() . DIRECTORY_SEPARATOR . $map->stored_filename);
                    }
                }

                $map->original_filename = $file->getClientOriginalName();
                $map->stored_filename = time() . '_' . $map->id_project . '_' . uniqid('projectmap') . '.' . strtolower($file->getClientOriginalExtension());

                if ($file->move(storage_path('app/public/map/'), $map->stored_filename)) {
                    $map->save();
                }
            }
            return request('success', 200);
        }
    }

    /**
     * Upload Maps.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function post(Request $request)
    {
        return;
    }

    /**
     * get file
     *
     *
     *
     */
    public function download($id)
    {
        // application/octet-stream

        $map = ProjectMapAttachment::where('id', $id)->first();
        if (!$map) return response('failed', 418);

        $file = storage_path() . DIRECTORY_SEPARATOR . $map->stored_filename;
        if (!file_exists($file)) return response('File tidak ditemukan', 418);

        $headers = ['Content-Type' =>  'application/octet-stream'];
        return  Response::download($file, $map->original_filename, $headers, 'attachment');
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Entity\ProjectMapAttachment  $projectMapAttachment
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return response()->json(
            ProjectMapAttachment::where('id_project', '=', $id)->where('file_type', '=', 'SHP')->get()
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Entity\ProjectMapAttachment  $projectMapAttachment
     * @return \Illuminate\Http\Response
     */
    public function edit(ProjectMapAttachment $projectMapAttachment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Entity\ProjectMapAttachment  $projectMapAttachment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ProjectMapAttachment $projectMapAttachment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Entity\ProjectMapAttachment  $projectMapAttachment
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProjectMapAttachment $projectMapAttachment)
    {
        //
    }

    public function getProjectMap()
    {
        $getProjectMap = DB::table('project_map_attachments')
            ->select('project_map_attachments.id_project', 'projects.project_title', 'project_map_attachments.attachment_type', 'project_map_attachments.stored_filename')
            ->leftJoin('projects', 'projects.id', '=', 'project_map_attachments.id_project')
            ->where('project_map_attachments.file_type', '=', 'SHP')
            ->get();

        return response()->json($getProjectMap);
    }
}
