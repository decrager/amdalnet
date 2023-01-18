<?php

namespace App\Http\Controllers;

use App\Entity\Announcement;
use App\Entity\Project;
use App\Http\Resources\AnnouncementResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use App\Entity\WorkflowLog;
use App\Notifications\AnnouncementNotification;
use App\User;
use Illuminate\Support\Facades\Notification;

// use DB;

class AnnouncementController extends Controller
{
    const view_all_is_true = 1;
    const view_all_is_false = 0;

    /**
     * Display a listing of the resource.
     *
     * @return AnnouncementResource
     */
    public function index(Request $request): AnnouncementResource
    {
        if ($request->sort == 'null') {
            $sort = 'DESC';
        } else {
            $sort = $request->sort;
        }

        $getAllAnnouncement = Announcement::with([
            'project' => function($q) {
                $q->with(['address',
                'initiator' => function($query) {
                    $query->select('id', 'email', 'logo');
                    $query->with('user', function($qu) {
                        $qu->select('id', 'email', 'avatar');
                    });
                }]);
            },
            // 'project.initiator'
        ])->withCount('feedbacks')
        ->select('announcements.*')
        ->addSelect(DB::raw('coalesce(initiators.logo, users.avatar) as applicant_logo'))
        ->addSelect(DB::raw('case when announcements.end_date::timestamp::date < now()::timestamp::date then true else false end as expired'))
        ->leftJoin('initiators', 'initiators.id', '=', 'announcements.id_applicant')
        ->leftJoin('users', 'users.email', '=', 'initiators.email')
        ->whereHas('project', function($q){
            $q->whereRaw('published is true');
            return $q;
        })
        ->where(function($query) use($request) {
            if($request->provName && ($request->provName !== null)) {
                $query->whereHas('project.address', function($q) use ($request) {
                    return $q->where('prov', '=', $request->provName);
                });
            }
            if($request->kotaName && ($request->kotaName !== null)) {
                $query->whereHas('project.address', function($q) use ($request) {
                    return $q->where('district', '=', $request->kotaName);
                });
            }
             if($request->has('keyword')) {
                $columnsToSearch = ['pic_name', 'project_type', 'project_location'];
                $searchQuery = '%' . $request->keyword . '%';
                $query->where('project_result', 'ILIKE', $searchQuery );
                foreach($columnsToSearch as $column) {
                    $query->orWhere($column, 'ILIKE', $searchQuery);
                }
            }
            return $query;
        })
        /*
        ->whereHas("project.address",function($q) use($request){
            $q->where("prov", "=", $request->provName);
        })
        ->orWhereHas("project.address",function($q) use($request){
            $q->where("district", "=", $request->kotaName);
        }) */

        /*->when($request->has('keyword'), function ($query) use ($request) {
            $columnsToSearch = ['pic_name', 'project_type', 'project_location'];
            $searchQuery = '%' . $request->keyword . '%';
            $indents = $query->where('project_result', 'ILIKE', '%'.$request->keyword.'%');
            foreach($columnsToSearch as $column) {
                $indents = $indents->orWhere($column, 'ILIKE', $searchQuery);
            }

            return $indents;
        })*/
        ->whereRaw('announcements.start_date::timestamp::date <= now()::timestamp::date')
        ->orderby('start_date', $sort ?? 'DESC')->paginate($request->limit ? $request->limit : 10);

        return AnnouncementResource::make($getAllAnnouncement);
    }

    /**
     * Display a listing of the resource.
     *
     * @return AnnouncementResource
     */
    public function getAnnouncementByFilter(Request $request): AnnouncementResource
    {
        if ($request->sort == 'null') {
            $sort = 'DESC';
        } else {
            $sort = $request->sort;
        }

        $getAllAnnouncement = Announcement::with([
            'project',
            //'project.province',
            'project.address'
        ])->withCount('feedbacks')
        ->select('announcements.*')
        ->addSelect(DB::raw('coalesce(initiators.logo, users.avatar) as applicant_logo'))
        ->addSelect(DB::raw('case when announcements.end_date::timestamp::date < now()::timestamp::date then true else false end as expired'))
        ->leftJoin('initiators', 'initiators.id', '=', 'announcements.id_applicant')
        ->leftJoin('users', 'users.email', '=', 'initiators.email')
        ->whereHas('project', function($q){
            $q->whereRaw('published is true');
            return $q;
        })
        ->whereRaw('announcements.start_date::timestamp::date <= now()::timestamp::date')
        /*->whereHas("project.address",function($q) use($request){
            $q->where("prov", "=", $request->provName);
        })
        ->orWhereHas("project.address",function($q) use($request){
            $q->where("district", "=", $request->kotaName);
        })*/
        ->where(function($query) use($request) {
            if($request->provName && ($request->provName !== 'null')) {
                $query->whereHas('project.address', function($q) use ($request) {
                    return $q->where('prov', '=', $request->provName);
                });
            }
            if($request->kotaName && ($request->kotaName !== 'null')) {
                $query->whereHas('project.address', function($q) use ($request) {
                    return $q->where('district', '=', $request->kotaName);
                });
            }
            if($request->has('keyword')) {
                $columnsToSearch = ['pic_name', 'project_type', 'project_location'];
                $searchQuery = '%' . $request->keyword . '%';
                $query->where('project_result', 'ILIKE', $searchQuery );
                foreach($columnsToSearch as $column) {
                    $query->orWhere($column, 'ILIKE', $searchQuery);
                }
            }
            return $query;
        })


        ->orderby('start_date', $sort ?? 'DESC')->paginate($request->limit ? $request->limit : 10);
        return AnnouncementResource::make($getAllAnnouncement);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //validate request
        $required = [
            'pic_name' => 'required',
            'pic_address' => 'required',
            'cs_name' => 'required',
            'cs_address' => 'required',
            'project_type' => 'required',
            'project_location' => 'required',
            'project_scale' => 'required',
            'potential_impact' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'is_done' => 'required',
            // 'fileProof' => 'required',
        ];
        $announcement = Announcement::where('project_id', $request->project_id)->first();
        if(!$announcement || ($announcement->proof == "")){
            if($request->project_result != "UKL-UPL"){
                $required['fileProof'] = 'required';
            }

        }

        // return $announcement->rawProof();

        $validator = Validator::make($request->all(), $required);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 403);
        } else {
            $params = $request->all();

            //create file
            $name = '';
            $file = $request->file('fileProof');
            if($file) {
                $name = 'announcement/' . uniqid() . '.' . $file->extension();
                $file->storePubliclyAs('public', $name);
            }

            DB::beginTransaction();

            // $announcement = Announcement::where('project_id', $params['project_id'])->first();
            if($announcement){
                $update_fields = [
                    'pic_name' => $params['pic_name'],
                    'pic_address' => $params['pic_address'],
                    'cs_name' => $params['cs_name'],
                    'cs_address' => $params['cs_address'],
                    'project_type' => $params['project_type'],
                    'project_location' => $params['project_location'],
                    'project_scale' => $params['project_scale'],
                    // 'proof' => $name,
                    'potential_impact' => $params['potential_impact'],
                    'start_date' => $params['start_date'],
                    'end_date' => $params['end_date'],
                    // 'project_id' => $params['project_id'],
                    'project_result' => $params['project_result'],
                    'id_applicant' => $params['id_applicant'],
                    'is_done' => $params['is_done'],
                ];
                if($file){
                    if (Storage::disk('public')->exists($announcement->rawProof()))
                    {
                        Storage::disk('public')->delete($announcement->rawProof());
                    }

                    $update_fields['proof'] = $name;
                }

                $announcement->update($update_fields);

            }else {
            //create Announcement
            // dd($params['is_done'], $params['id_applicant']);
                $announcement = Announcement::create([
                    'pic_name' => $params['pic_name'],
                    'pic_address' => $params['pic_address'],
                    'cs_name' => $params['cs_name'],
                    'cs_address' => $params['cs_address'],
                    'project_type' => $params['project_type'],
                    'project_location' => $params['project_location'],
                    'project_scale' => $params['project_scale'],
                    'proof' => $name,
                    'potential_impact' => $params['potential_impact'],
                    'start_date' => $params['start_date'],
                    'end_date' => $params['end_date'],
                    'project_id' => $params['project_id'],
                    'project_result' => $params['project_result'],
                    'id_applicant' => $params['id_applicant'],
                    'is_done' => $params['is_done'],
                ]);
            }

            if($params['publish'] && ($params['publish'] === 'true')){

                $project = Project::find($params['project_id']);
                $projectUkl = Project::find($params['project_id'])->required_doc;
                $project->published = true;
                $project->save();

                switch ($project->marking){
                    case 'announcement-drafting':
                        $project->workflow_apply('announce');
                        if($projectUkl === 'UKL-UPL') {
                            $project->workflow_apply('complete-announcement');
                        }
                        $project->save();
                        break;
                    case 'formulator-assignment':
                        if($projectUkl === 'AMDAL') {
                            $project->applyWorkFlowTransition('announce', 'draft-announcement', 'announcement');
                        }
                        if($projectUkl === 'UKL-UPL') {
                            $project->applyWorkFlowTransition('complete-announcement', 'announcement', 'announcement-completed');
                        }
                        break;
                    case 'screening-completed':
                        $project->applyWorkFlowTransition('assign-formulator', 'screening-completed', 'formulator-assignment');
                        if($projectUkl === 'AMDAL') {
                            $project->applyWorkFlowTransition('announce', 'draft-announcement', 'announcement');
                        }
                        if($projectUkl === 'UKL-UPL') {
                            $project->applyWorkFlowTransition('complete-announcement', 'announcement', 'announcement-completed');
                        }
                        break;
                    default:
                }

                // === NOTIFIKASI === //
                $user = User::where('email', $project->initiator->email)->first();
                if($user) {
                    Notification::send([$user], new AnnouncementNotification($project));
                }
            }

            if (!$announcement) {
                DB::rollback();
            } else {
                DB::commit();
            }

            return new AnnouncementResource($announcement);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param Announcement $announcement
     * @return Response
     */
    public function show(Announcement $announcement)
    {
        return Announcement::with([
            'project',
            'project.province',
            'project.address',
            'initiator',
        ])->get()->where('id', '=', $announcement->id)->first();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Announcement $announcement
     * @return Response
     */
    public function edit(Announcement $announcement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Announcement $announcement
     * @return Response
     */
    public function update(Request $request, Announcement $announcement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Announcement $announcement
     * @return Response
     */
    public function destroy(Announcement $announcement)
    {
        //
    }
}
