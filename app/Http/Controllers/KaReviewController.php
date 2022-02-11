<?php

namespace App\Http\Controllers;

use App\Entity\FormulatorTeam;
use App\Entity\FormulatorTeamMember;
use App\Entity\KaReview;
use App\Entity\Project;
use App\Laravue\Models\User;
use App\Notifications\KaReview as AppKaReview;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class KaReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $review = KaReview::where('id_project', $request->idProject)->orderBy('id', 'desc')->first();
        
        if($review) {
            $review_penyusun = KaReview::where([['id_project', $request->idProject],['status', 'submit-to-pemrakarsa']])->orderBy('id', 'desc')->first();
            $review->setAttribute('formulator_notes', $review_penyusun->notes);
        }
        
        return $review;
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
        if($request->type == 'penyusun') {
            $review = new KaReview();
            $review->id_project = $request->idProject;
            $review->status = 'submit-to-pemrakarsa';
            $review->notes = $request->notes;
            $review->save();

            // === NOTIFICATIONS === //
            $project = Project::findOrFail($request->idProject);
            $email = $project->initiator->email;
            $user = User::where('email', $email)->count();

            if($user > 0) {
                $user = User::where('email', $email)->first();
                Notification::send([$user], new AppKaReview($review));
            }

            // === WORKFLOW === //
            // if($project->marking == 'amdal.form-ka-drafting') {
            //     $project->workflow_apply('submit-amdal-form-ka');
            //     $project->save();
            // }
        }

        if($request->type == 'pemrakarsa') {
            $project = Project::findOrFail($request->idProject);

            $review = new KaReview();
            $review->id_project = $request->idProject;
            $review->status = $request->status;
            
            if($request->status == 'revisi') {
                $review->notes = $request->notes;
            } else if($request->status == 'submit') {
                if($request->hasFile('file')) {
                    if ($request->file('file')) {
                        $file = $request->file('file');
                        $fileName = 'ka-reviews/' . uniqid() . '.' . $file->extension();
                        $file->storePubliclyAs('public', $fileName);
                        $review->application_letter = Storage::url($fileName);
                    }
                }
            }

            $review->save();

            if($request->status == 'revisi') {
                $formulator_team = FormulatorTeam::where('id_project', $request->idProject)->first();
                if($formulator_team) {
                    $ketua = FormulatorTeamMember::where([['id_formulator_team', $formulator_team->id],['position', 'Ketua']])->first();
                    if($ketua) {
                        if($ketua->formulator) {
                            $email = $ketua->formulator->email;
                            $user = User::where('email', $email)->count();
                            if($user > 0) {
                                $user = User::where('email', $email)->first();
                                Notification::send([$user], new AppKaReview($review));
                            }
                        }
                    }
                }
                // === WORKFLOW === //
                // if($project->marking == 'amdal.form-ka-adm-review') {
                //     $project->workflow_apply('return-amdal-form-ka-review');
                //     $project->save();
                // }
            } else if($request->status == 'submit') {
                // if($project->marking == 'amdal.form-ka-adm-review') {
                //     $project->workflow_apply('approve-amdal-form-ka');
                //     $project->save();
                // } else if($project->marking == 'amdal.form-ka-adm-returned') {
                //     $project->workflow_apply('approve-amdal-form-ka');
                //     $project->save();
                // }
            }
        }

        return response()->json(['message' => 'Data saved successfully']);
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
}