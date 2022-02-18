<?php

namespace App\Http\Controllers;

use App\Entity\FeasibilityTestTeam;
use App\Entity\FeasibilityTestTeamMember;
use App\Entity\FormulatorTeam;
use App\Entity\FormulatorTeamMember;
use App\Entity\GovernmentInstitution;
use App\Entity\Initiator;
use App\Entity\Lpjp;
use App\Entity\Project;
use App\Entity\TestingMeeting;
use App\Entity\TestingMeetingInvitation;
use App\Entity\TestingVerification;
use App\Laravue\Models\User;
use App\Notifications\MeetingInvitation;
use App\Utils\Html;
use App\Utils\TemplateProcessor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\Element\Table;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class TestMeetRKLRPLController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->meetingInvitation) {
            $document_type = $request->uklUpl ? 'ukl-upl' : 'rkl-rpl';
            return $this->meetingInvitation($request->idProject, $document_type);
        }

        if($request->dokumen) {
            $document_type = $request->uklUpl ? 'ukl-upl' : 'rkl-rpl';
            return $this->dokumen($request->idProject, $document_type);
        }

        if($request->pemrakarsa) {
            return Initiator::where('user_type', 'Pemrakarsa')->get();
        }

        if($request->expert_bank_team) {
            return FeasibilityTestTeam::with(['provinceAuthority', 'districtAuthority'])->get();
        }

        if($request->tuk_member) {
            $members = FeasibilityTestTeamMember::where('id_feasibility_test_team', $request->tuk_id)->get();
            $newMembers = [];
            
            foreach($members as $m) {
                $name = '';
                $email = '';
                $type_member = '';

                if($m->expertBank) {
                    $name = $m->expertBank->name;
                    $email = $m->expertBank->email;
                    $type_member = 'expert';
                } else if($m->lukMember) {
                    $name = $m->lukMember->name;
                    $email = $m->lukMember->email;
                    $type_member = 'employee';
                }

                $newMembers[] = [
                    'id' => $m->id,
                    'role' => $m->position,
                    'name' => $name,
                    'email' => $email,
                    'type' => 'tuk',
                    'type_member' => $type_member
                ];
            }

            return $newMembers;
        }

        if($request->idProject) {
            // Check if meeting exist
            $document_type = $request->uklUpl ? 'ukl-upl' : 'rkl-rpl';
            $meetings = TestingMeeting::where([['id_project', $request->idProject], ['document_type', $document_type]])->first();
            if($meetings) {
                return $this->getExistMeetings($request->idProject, $document_type);
            } else {
                return $this->getFreshMeetings($request->idProject);
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
        if($request->invitation) {
            $document_type = $request->uklUpl ? 'ukl-upl' : 'rkl-rpl';
            $receiver = [];
            $meeting = TestingMeeting::where([['id_project', $request->idProject],['document_type', $document_type]])->first();
            if($meeting) {
                $invitations = TestingMeetingInvitation::where('id_testing_meeting', $meeting->id)->get();
                foreach($invitations as $i) {
                    if($i->id_feasibility_test_team_member) {
                        $member = FeasibilityTestTeamMember::find($i->id_feasibility_test_team_member);
                        $email = null;
                        if($member->expertBank) {
                            $email = $member->expertBank->email;
                        } else if($member->lukMember) {
                            $email = $member->lukMember->email;
                        }

                        if($email) {
                            $user = User::where('email', $email)->first();
                            if($user) {
                                $receiver[] = $user;
                            }
                        }
                    }
                }
            }

            if(count($receiver) > 0) {
                $this->meetingInvitation($request->idProject, $document_type);
                Notification::send($receiver, new MeetingInvitation($meeting));
                return response()->json(['error' => 0, 'message', 'Notifikasi Sukses Terkirim']);

                // === WORKFLOW === //
                // $project = Project::findOrFail($request->idProject);
                // if($project->marking == 'uklupl-mt.examination-invitation-drafting') {
                //     $project->workflow_apply('send-uklupl-examination-invitation');
                //     $project->workflow_apply('examine-uklupl');
                //     $project->workflow_apply('held-uklupl-examination-meeting');
                //     $project->save();
                // } else if($project->marking == 'amdal.feasibility-invitation-drafting') {
                //     $project->workflow_apply('send-amdal-feasibility-invitation');
                //     $project->workflow_apply('review-amdal-feasibility');
                //     $project->workflow_apply('held-amdal-feasibility-meeting');
                //     $project->save();
                // }
                
            }

            return response()->json(['error' => 1, 'message' => 'Kirim Notifikasi Gagal']);
        }

        if($request->file) {
            $document_type = $request->uklUpl ? 'ukl-upl' : 'rkl-rpl';
            $data = $request->all();
            $validator = \Validator::make($data, [
                'dokumen_file' => 'max:1024'
            ],[
                'dokumen_file.max' => 'Ukuran file tidak boleh melebihi 1 mb'
            ]);

            if($validator->fails()) {
                return response()->json(['errors' => $validator->messages()]);
            }

            if($request->hasFile('dokumen_file')) {
                $document_type = $request->uklUpl ? 'ukl-upl' : 'rkl-rpl';
                $project = Project::findOrFail($request->idProject);
                $file = $request->file('dokumen_file');
                $name = '/verifikasi-' . $document_type . '/' . strtolower($project->project_title) . '.' . $file->extension();
                $file->storePubliclyAs('public', $name);
    
                $testing_meeting = TestingMeeting::where([['id_project', $request->idProject], ['document_type', $document_type]])->first();
                $testing_meeting->file = Storage::url($name);
                $testing_meeting->save();

            } else {
                return response()->json(['errors' => ['dokumen_file' => ['Dokumen Tidak Valid']]]);
            }

            return response()->json(['errors' => null, 'name' => $testing_meeting->file]);
        }

        $data = $request->meetings;
        $document_type = $request->uklUpl ? 'ukl-upl' : 'rkl-rpl';

        // Save meetings
        $meeting = null;
        $oldTeamId = null;
        if($data['type'] == 'new') {
            $meeting = new TestingMeeting();
            $meeting->id_project = $request->idProject;
            $meeting->document_type = $document_type;
        } else {
            $meeting = TestingMeeting::where([['id_project', $request->idProject],['document_type', $document_type]])->first();
            $oldTeamId = $meeting->id_feasibility_test_team;
        }


        $meeting->meeting_date = $data['meeting_date'];
        $meeting->meeting_time = $data['meeting_time'];
        $meeting->location = $data['location'];
        $meeting->position = $data['position'];
        $meeting->id_feasibility_test_team = $data['id_feasibility_test_team'];
        $meeting->id_initiator = $data['id_initiator'];
        $meeting->save();

        // Delete existing invitations if expert bank team is different
        if($data['type'] == 'update') {
            if($oldTeamId != $data['id_feasibility_test_team']) {
                TestingMeetingInvitation::where([['id_testing_meeting', $meeting->id]])->delete();
            } else {
                for($a = 0; $a < count($data['deleted_invitations']); $a++) {
                    TestingMeetingInvitation::destroy($data['deleted_invitations'][$a]);
                }           
            }
        }

        // Save meetings invitation members
        for($i = 0; $i < count($data['invitations']); $i++) {
            if($data['type'] == 'new') {
                $invitation = new TestingMeetingInvitation();
                $invitation->id_feasibility_test_team_member = $data['invitations'][$i]['type'] == 'tuk' ? $data['invitations'][$i]['id'] : null;
                $invitation->id_testing_meeting = $meeting->id;

                if($data['invitations'][$i]['type'] == 'other') {
                    $invitation->role = $data['invitations'][$i]['role'];
                    $invitation->name = $data['invitations'][$i]['name'];
                    $invitation->email = $data['invitations'][$i]['email'];
                    $invitation->institution = $data['invitations'][$i]['institution'];
                    $invitation->id_government_institution = $data['invitations'][$i]['id_government_institution'];
                }
    
                $invitation->save();
            } else {
                $invitation = new TestingMeetingInvitation();

                if($data['invitations'][$i]['type'] == 'tuk' && $oldTeamId == $data['id_feasibility_test_team']) {
                    continue;
                }

                if($data['invitations'][$i]['type'] == 'other') {
                    $invitation = TestingMeetingInvitation::where('email', $data['invitations'][$i]['email'])->first();

                    if(!$invitation) {
                        $invitation = new TestingMeetingInvitation();
                    }

                    $invitation->role = $data['invitations'][$i]['role'];
                    $invitation->name = $data['invitations'][$i]['name'];
                    $invitation->email = $data['invitations'][$i]['email'];
                    $invitation->institution = $data['invitations'][$i]['institution'];
                    $invitation->id_government_institution = $data['invitations'][$i]['id_government_institution'];
                }

                $invitation->id_feasibility_test_team_member = $data['invitations'][$i]['type'] == 'tuk' ? $data['invitations'][$i]['id'] : null;
                $invitation->id_testing_meeting = $meeting->id;
                $invitation->save();
            }

        }

        // === WORKFLOW === //
        // if($document_type == 'rkl-rpl') {
        //     $project = Project::findOrFail($request->idProject);
        //     if($project->marking == 'amdal.examination') {
        //         $project->workflow_apply('draft-amdal-feasibility-invitation');
        //         $project->save();
        //     }
        // }

        return response()->json(['message' => 'success']);
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

    private function getFreshMeetings($id_project) {
        $project = Project::findOrFail($id_project);

        $data = [
            'type' => 'new',
            'id_project' => $id_project,
            'initiator_name' => $project->initiator->name,
            'id_initiator' => $project->initiator->id,
            'meeting_date' => null,
            'meeting_time' => null,
            'person_responsible' => $project->initiator->pic,
            'location' => null,
            'position' => null,
            'expert_bank_team_id' => null,
            'id_feasibility_test_team' => null,
            'project_name' => $project->project_title,
            'invitations' => [],
            'file' => null,
            'deleted_invitations' => []
        ];

        return $data;
    }

    private function getExistMeetings($id_project, $document_type) {
        $meeting = TestingMeeting::where([['id_project', $id_project],['document_type', $document_type]])->first();

        $invitations = [];

        if($meeting->invitations->first()) {
            foreach($meeting->invitations as $i) {
                if($i->id_feasibility_test_team_member) {
                    $name = '';
                    $email = '';
                    $type_member = '';
                    $institution = '-';

                    if($i->feasibilityTestTeamMember->id_expert_bank) {
                        $name = $i->feasibilityTestTeamMember->expertBank->name;
                        $email = $i->feasibilityTestTeamMember->expertBank->email;
                        $type_member = 'expert';
                    } else if($i->feasibilityTestTeamMember->id_luk_member) {
                        $name = $i->feasibilityTestTeamMember->lukMember->name;
                        $email = $i->feasibilityTestTeamMember->lukMember->email;
                        $institution = $i->feasibilityTestTeamMember->lukMember->institution;
                        $type_member = 'employee';
                    }

                    $invitations[] = [
                        'id' => $i->id_feasibility_test_team_member,
                        'role' => $i->feasibilityTestTeamMember->position,
                        'name' => $name,
                        'email' => $email,
                        'type' => 'tuk',
                        'type_member' => $type_member,
                        'institution' => $institution,
                        'id_government_institution' => null
                    ];
                } else {
                    $invitations[] = [
                        'id' => $i->id,
                        'role' => $i->role,
                        'name' => $i->name,
                        'email' => $i->email,
                        'type' => 'other',
                        'type_member' => 'other',
                        'institution' => $i->institution,
                        'id_government_institution' => $i->id_government_institution,
                    ];
                }
            }

            // Make expert bank team on top
            usort($invitations, function($a, $b) {
                if($a['type']==$b['type']) return 0;
                return $a['type'] < $b['type']?1:-1;
            });
        } 

        $data = [
            'type' => 'update',
            'id_project' => $id_project,
            'id_initiator' => $meeting->project->initiator->id,
            'initiator_name' => $meeting->project->initiator->name,
            'meeting_date' => $meeting->meeting_date,
            'meeting_time' => $meeting->meeting_time,
            'person_responsible' => $meeting->project->initiator->pic,
            'location' => $meeting->location,
            'position' => $meeting->position,
            'expert_bank_team_id' => $meeting->expert_bank_team_id,
            'id_feasibility_test_team' => $meeting->id_feasibility_test_team,
            'project_name' => $meeting->project->project_title,
            'invitations' => $invitations,
            'file' => $meeting->file,
            'deleted_invitations' => []
        ];

        return $data;
    }

    private function dokumen($id_project, $document_type) {
        if (!File::exists(storage_path('app/public/adm/'))) {
            File::makeDirectory(storage_path('app/public/adm/'));
        }

        $project = Project::findOrFail($id_project);
        $testing_meeting = TestingMeeting::where([['id_project', $id_project], ['document_type', $document_type]])->first();
        $verification = TestingVerification::where([['id_project', $id_project], ['document_type', $document_type]])->first();
        Carbon::setLocale('id');

        $docs_date = Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->isoFormat('D MMMM Y');

        $project_address = '';
        if($project->address) {
            if($project->address->first()) {
                $project_address = $project->address->first()->address . ' ' . ucwords(strtolower($project->address->first()->district)) . ' Provinsi ' . ucwords(strtolower($project->address->first()->prov));
            }
        }
        
        $meeting_time = '';
        $meeting_date = '';
        
        if($testing_meeting->meeting_time) {
            $meeting_time = date('H:i', strtotime($testing_meeting->meeting_time));
        }

        if($testing_meeting->meeting_date) {
            $meeting_date = Carbon::createFromFormat('Y-m-d', $testing_meeting->meeting_date)->isoFormat('D MMMM Y');
        }

        $ketua_tuk = '';
        if($testing_meeting->id_feasibility_test_team) {
            $member = FeasibilityTestTeamMember::where([['id_feasibility_test_team', $testing_meeting->id_feasibility_test_team], ['position', 'Ketua']])->first();
            if($member) {
                if($member->expertBank) {
                    $ketua_tuk = $member->expertBank->name;
                } else if($member->lukMember) {
                    $ketua_tuk = $member->lukMember->name;
                }
            }
        }

        $tim_penyusun = '';
         if($project->type_formulator_team == 'lpjp') {
             $lpjp_data = Lpjp::find($project->id_lpjp);
             if($lpjp_data) {
                 $tim_penyusun = $lpjp_data->name . ' (' . $lpjp_data->reg_no . ')';
             }
         } else if($project->type_formulator_team == 'mandiri') {
             $mandiri_data = FormulatorTeam::where('id_project', $project->id)->first();
             if($mandiri_data) {
                $tim_penyusun = 'Tim Penyusun Mandiri';
             }
         }

         $anggota_penyusun = [];
         $formulator_team = FormulatorTeam::where('id_project', $id_project)->first();
         if($formulator_team) {
             $formulator_member = FormulatorTeamMember::where('id_formulator_team', $formulator_team->id)->orderBy('position', 'desc')->get();
             $total = 0;
             $ahli = [];
             foreach($formulator_member as $f) {
                 if($f->formulator) {
                     $total++;
                     $anggota_penyusun[] = [
                         'penyusun' => $total . '. ' .  $f->formulator->name . ' (' . $f->position . ')' 
                     ];
                 } else if($f->expert) {
                    $ahli[] = $f->expert->name . ' (Anggota Ahli)';
                 }
             }

             if(count($ahli) > 0) {
                 $total = count($anggota_penyusun) + 1;
                 for($i = 0; $i < count($ahli); $i++) {
                    $anggota_penyusun[] = [
                         'penyusun' => $total . '. ' . $ahli[$i]
                        ];
                    $total++;
                } 
             }
         }

         $meeting_invitations = [];
         $invitations = TestingMeetingInvitation::where('id_testing_meeting', $testing_meeting->id)->get();
         $total = 1;
         foreach($invitations as $i) {
             if($i->id_feasibility_test_team_member) {
                $member = FeasibilityTestTeamMember::find($i->id_feasibility_test_team_member);
                if($member) {
                    if($member->expertBank) {
                        $meeting_invitations[] = [
                            'invitations' => $total . '. ' . $member->expertBank->name . ' (Pakar ' . $member->expertBank->expertise . ')'
                        ];
                    } else if($member->lukMember) {
                        $meeting_invitations[] = [
                            'invitations' => $total . '. ' . $member->lukMember->position . ' ' . $member->lukMember->institution
                        ];
                    }
                }
             } else {
                 $meeting_invitations[] = [
                   'invitations' => $total . '. ' . $i->name . ' (' . $i->role . ')'  
                 ];
             }
             $total++;
         }

        //  AUTHORITY && LOGO
        $authority = '';
        $tuk_logo = null;
        if($testing_meeting->id_feasibility_test_team) {
             $team = FeasibilityTestTeam::find($testing_meeting->id_feasibility_test_team);
             if($team) {
                 if($team->authority == 'Pusat') {
                     $authority = 'PUSAT';
                 } else if($team->authority == 'Provinsi') {
                     $authority = 'PROVINSI' . strtoupper($team->provinceAuthority->name);
                 } else if($team->authority == 'Kabupaten/Kota') {
                     $authority = strtoupper($team->districtAuthority->name);
                 }
                 $tuk_logo = $team->logo;
             }
         }

        $templateProcessor = new TemplateProcessor('template_berkas_adm_ar_yes.docx');
        if($document_type == 'ukl-upl') {
            $templateProcessor = new TemplateProcessor('template_berkas_adm_uu_yes.docx');
        }

        if($tuk_logo) {
            $templateProcessor->setImageValue('logo_tuk', substr(str_replace('//', '/', $tuk_logo), 1));
        } else {
            $templateProcessor->setImageValue('logo_tuk', 'images/logo-klhk-doc.jpg');
        }

        $templateProcessor->setValue('authority', $authority);
        $templateProcessor->setValue('project_title', $project->project_title);
        $templateProcessor->setValue('pemrakarsa', $project->initiator->name);
        $templateProcessor->setValue('project_description', $project->description);
        $templateProcessor->setValue('project_location', $project_address);
        $templateProcessor->setValue('meeting_time', $meeting_time . ' ' . $meeting_date);
        $templateProcessor->setValue('docs_date', $docs_date);
        $templateProcessor->setValue('ketua_tuk', $ketua_tuk);
        $templateProcessor->setValue('tim_penyusun', $tim_penyusun);
        $templateProcessor->cloneBlock('anggota_penyusun', count($anggota_penyusun), true, false, $anggota_penyusun);
        $templateProcessor->cloneBlock('meeting_invitations', count($meeting_invitations), true, false, $meeting_invitations);

        if($verification->forms) {
            if($verification->forms->first()) {
                foreach($verification->forms as $f) {
                    $templateProcessor->setValue($f->name . '_yes', $f->suitability == 'Sesuai' ? 'V' : '');
                    $templateProcessor->setValue($f->name . '_no', $f->suitability == 'Tidak Sesuai' ? 'V' : '');
                    $templateProcessor->setValue($f->name . '_ket', $f->description);
                }
            }
        }

        $notesTable = new Table();
        $notesTable->addRow();
        $cell = $notesTable->addCell(6000);
        Html::addHtml($cell, $verification->notes);

        $templateProcessor->setComplexBlock('notes', $notesTable);
        
        if($document_type == 'ukl-upl') {
            $templateProcessor->saveAs(storage_path('app/public/adm/berkas-adm-uu-' . strtolower($project->project_title) . '.docx'));
        } else {
            $templateProcessor->saveAs(storage_path('app/public/adm/berkas-adm-ar-' . strtolower($project->project_title) . '.docx'));
        }

        return strtolower($project->project_title);
    }

    private function meetingInvitation($id_project, $document_type)
    {
        if (!File::exists(storage_path('app/public/meet-inv/'))) {
            File::makeDirectory(storage_path('app/public/meet-inv/'));
        }

        $project = Project::findOrFail($id_project);
        $testing_meeting = TestingMeeting::select('id', 'id_project', 'id_feasibility_test_team', 'updated_at', 'location', 'meeting_date', 'meeting_time')->where([['id_project', $id_project],['document_type', $document_type]])->first();
        $invitations = TestingMeetingInvitation::where('id_testing_meeting', $testing_meeting->id)->get();
        Carbon::setLocale('id');
        
        $docs_date = Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', strtotime($testing_meeting->updated_at)))->isoFormat('MMMM Y');

        $project_address = '';
        if($project->address) {
            if($project->address->first()) {
                $project_address = $project->address->first()->address . ' ' . ucwords(strtolower($project->address->first()->district)) . ' Provinsi ' . ucwords(strtolower($project->address->first()->prov));
            }
        }

        // Meeting
        $meeting_date = Carbon::createFromFormat('Y-m-d', date('Y-m-d', strtotime($testing_meeting->meeting_date)))->isoFormat('D, MMMM Y');
        $meeting_time = date('H:i', strtotime($testing_meeting->meeting_time));
        $meeting_location = $testing_meeting->location;

        // TUK
        $authority_big_check = '';
        $authority = '';
        $authority_big = '';
        $tuk_address = '';
        $ketua_tuk_name = '';
        $ketua_tuk_nip = '';
        $tuk_logo = null;


        if($testing_meeting->id_feasibility_test_team) {
            $team = FeasibilityTestTeam::find($testing_meeting->id_feasibility_test_team);
            if($team) {
                if($team->authority == 'Pusat') {
                    $authority_big_check = 'PUSAT';
                    $authority_big = 'PUSAT';
                    $authority = 'Pusat';
                } else if($team->authority == 'Provinsi') {
                    $authority_big_check = 'PROVINSI' . strtoupper($team->provinceAuthority->name);
                    $authority_big = 'PROVINSI' . strtoupper($team->provinceAuthority->name);
                    $authority = 'Provinsi ' . ucwords(strtolower($team->provinceAuthority->name)); 
                } else if($team->authority == 'Kabupaten/Kota') {
                    $authority_big_check = strtoupper($team->districtAuthority->name);
                    $authority_big = strtoupper($team->districtAuthority->name);
                    $authority = ucwords(strtolower($team->districtAuthority->name));
                }

                $tuk_address = $team->address;
                $tuk_logo = $team->logo;

                // KETUA TUK
                $ketua_tuk = FeasibilityTestTeamMember::where([['id_feasibility_test_team', $team->id],['position', 'Ketua']])->first();
                if($ketua_tuk) {
                    if($ketua_tuk->expertBank) {
                        $ketua_tuk_name = $ketua_tuk->expertBank->name;
                    } else if($ketua_tuk->lukMember) {
                        $ketua_tuk_name = $ketua_tuk->lukMember->name;
                        $ketua_tuk_nip = $ketua_tuk->lukMember->nip ? 'NIP. ' . $ketua_tuk->lukMember->nip : '';
                    }
                }


            }

        }
        
        $member = [];
        $ahli = [];
        $instansi = [];
        
        foreach($invitations as $i) {
            if($i->id_feasibility_test_team_member) {
                $tuk_member = FeasibilityTestTeamMember::find($i->id_feasibility_test_team_member);
                if($tuk_member) {
                    if($tuk_member->position != 'Ketua') {
                        if($tuk_member->expertBank) {
                            $member[] = [
                                'name' => count($member) + 1 . '. ' . $tuk_member->expertBank->name . ' (Pakar ' . $tuk_member->expertBank->expertise . ')'
                            ];
                        } else if($tuk_member->lukMember) {
                            $member[] = [
                                'name' => count($member) + 1 . '. ' . $tuk_member->lukMember->name . ', ' . $tuk_member->lukMember->position. ' ' . $tuk_member->lukMember->institution
                            ];
                        }
                    }
                }
            } else if($i->id_government_institution) {
                $institution = GovernmentInstitution::find($i->id_government_institution);
                if($institution) {
                    $instansi[] = [
                        'name' => count($instansi) + 1 . '. Wakil dari ' . $institution->name
                    ];
                } 
            } else {
                if($i->role == 'Tenaga Ahli') {
                    $member[] = [
                        'name' => count($ahli) + 1 . '. ' . $i->name
                    ];
                }
            }
        }

        $templateProcessor = new TemplateProcessor('template_undangan_rapat_arr.docx');
        if($document_type == 'ukl-upl') {
            $templateProcessor = new TemplateProcessor('template_undangan_rapat_uu.docx');
        }

        if($tuk_logo) {
            $templateProcessor->setImageValue('logo_tuk', substr(str_replace('//', '/', $tuk_logo), 1));
        } else {
            $templateProcessor->setImageValue('logo_tuk', 'images/logo-klhk-doc.jpg');
        }

        $instansi[] = ['name' => count($instansi) + 1 . '. Kementerian/Lembaga/Dinas yang terkait Usaha dan/atau Kegiatan'];
        $instansi[] = ['name' => count($instansi) + 1 . '. Kementerian/Lembaga/Dinas yang terkait Persetujuan Awal'];
        $instansi[] = ['name' => count($instansi) + 1 . '. Kementerian/Lembaga/Dinas yang penerbit Pertek'];

        $templateProcessor->setValue('project_title', $project->project_title);
        $templateProcessor->setValue('project_address', $project_address);
        $templateProcessor->setValue('pemrakarsa', $project->initiator->name);
        $templateProcessor->setValue('pemrakarsa_address', $project->initiator->address);
        $templateProcessor->setValue('docs_date', $docs_date);
        $templateProcessor->setValue('meeting_date', $meeting_date);
        $templateProcessor->setValue('meeting_time', $meeting_time);
        $templateProcessor->setValue('meeting_location', $meeting_location);
        $templateProcessor->setValue('authority_big_check', $authority_big_check);
        $templateProcessor->setValue('authority_big', $authority_big);
        $templateProcessor->setValue('tuk_address', $tuk_address);
        $templateProcessor->setValue('authority', $authority);
        $templateProcessor->setValue('ketua_tuk_name', $ketua_tuk_name);
        $templateProcessor->setValue('ketua_tuk_nip', $ketua_tuk_nip);
        $templateProcessor->cloneBlock('tuk_member', count($member), true, false, $member);
        $templateProcessor->cloneBlock('pakar', count($ahli), true, false, $ahli);
        $templateProcessor->cloneBlock('instansi', count($instansi), true, false, $instansi);

        if($document_type == 'ukl-upl') {
            $templateProcessor->saveAs(storage_path('app/public/meet-inv/ukl-upl-' . strtolower($project->project_title) . '.docx'));
        } else {
            $templateProcessor->saveAs(storage_path('app/public/meet-inv/andal-rkl-rpl-' . strtolower($project->project_title) . '.docx'));
        }

        return strtolower($project->project_title);
    }
}
