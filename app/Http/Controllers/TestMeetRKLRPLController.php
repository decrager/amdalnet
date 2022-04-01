<?php

namespace App\Http\Controllers;

use App\Entity\EnvManageDoc;
use App\Entity\FeasibilityTestTeam;
use App\Entity\FeasibilityTestTeamMember;
use App\Entity\FormulatorTeam;
use App\Entity\FormulatorTeamMember;
use App\Entity\GovernmentInstitution;
use App\Entity\Initiator;
use App\Entity\Lpjp;
use App\Entity\Project;
use App\Entity\ProjectMapAttachment;
use App\Entity\PublicConsultation;
use App\Entity\TestingMeeting;
use App\Entity\TestingMeetingInvitation;
use App\Entity\TestingVerification;
use App\Entity\TukProject;
use App\Entity\TukSecretaryMember;
use App\Laravue\Acl;
use App\Laravue\Models\Role;
use App\Laravue\Models\User;
use App\Notifications\MeetingInvitation;
use App\Utils\Html;
use App\Utils\TemplateProcessor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpWord\Element\Table;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
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

        if($request->tukMember) {
            return $this->getTukMember($request->idProject);
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
            $receiver_non_user = [];
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
                        
                    } else if($i->id_tuk_secretary_member) {
                        $secretary = TukSecretaryMember::find($i->id_tuk_secretary_member);
                        if($secretary) {
                            $user = User::where('email', $secretary->email)->first();
                            if($user) {
                                $receiver[] = $user;
                            }
                        }
                    } else if($i->email) {
                        $user = User::where('email', $i->email)->count();
                        if($user === 0) {
                            $role = Role::findByName(Acl::ROLE_EXAMINER);
                            $user = User::create([
                                'name' => ucfirst($i->name),
                                'email' => $i->email,
                                'password' => Hash::make('amdalnet')
                            ]);
                            $user->syncRoles($role);
                        } else {
                            $user = User::where('email', $i->email)->first();
                        }

                        $receiver[] = $user;
                    }
                }
            }

            if((count($receiver) > 0) || (count($receiver_non_user) > 0)) {
                if(count($receiver) > 0) {
                    Notification::send($receiver, new MeetingInvitation($meeting));
                }

                if(count($receiver_non_user) > 0) {
                    Notification::route('mail', $receiver_non_user)->notify(new MeetingInvitation($meeting));
                }

                // === UPDATE INVITATION STATUS === //
                $meeting->is_invitation_sent = true;
                $meeting->save();

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

        $data = json_decode($request->meetings, true);
        $document_type = $request->uklUpl ? 'ukl-upl' : 'rkl-rpl';

        // Save meetings
        $meeting = null;
        if($data['type'] == 'new') {
            $meeting = new TestingMeeting();
            $meeting->id_project = $request->idProject;
            $meeting->document_type = $document_type;
        } else {
            $meeting = TestingMeeting::where([['id_project', $request->idProject],['document_type', $document_type]])->first();
        }


        $meeting->meeting_date = $data['meeting_date'];
        $meeting->meeting_time = $data['meeting_time'];
        $meeting->location = $data['location'];

        // Invitation File
        if($request->hasFile('invitation_file')) {
            $project = Project::findOrFail($request->idProject);
            $file = $request->file('invitation_file');
            $folder = $document_type === 'ukl-upl' ? 'ukl-upl' : 'andal-rkl-rpl';
            $name = '/meeting-' . $folder . '/' . strtolower($project->project_title) . '.' . $file->extension();
            $file->storePubliclyAs('public', $name);

            $meeting->invitation_file = Storage::url($name);

        }

        $meeting->save();

        // Delete invitations
        if(count($data['deleted_invitations']) > 0) {
            TestingMeetingInvitation::whereIn('id', $data['deleted_invitations'])->delete();
        }

        // Save meetings invitation members
        for($i = 0; $i < count($data['invitations']); $i++) {
            $invitation = null;

            if($data['invitations'][$i]['id'] == null) {
                $invitation = new TestingMeetingInvitation();
                $invitation->id_testing_meeting = $meeting->id;
            } else {
                $invitation = TestingMeetingInvitation::findOrFail($data['invitations'][$i]['id']);
            }

            if($data['invitations'][$i]['type'] == 'Ketua TUK' || $data['invitations'][$i]['type'] == 'Anggota TUK') {
                $invitation->id_feasibility_test_team_member = $data['invitations'][$i]['tuk_member_id'];
            } else if($data['invitations'][$i]['type'] == 'Anggota Sekretariat TUK') {
                $invitation->id_tuk_secretary_member = $data['invitations'][$i]['tuk_member_id'];
            } else if($data['invitations'][$i]['type'] == 'institution' || $data['invitations'][$i]['type'] == 'other') {
                $invitation->role = $data['invitations'][$i]['role'];
                $invitation->name = $data['invitations'][$i]['name'];
                $invitation->email = $data['invitations'][$i]['email'];
                $invitation->institution = $data['invitations'][$i]['institution'];

                if($data['invitations'][$i]['type'] == 'institution') {
                    $invitation->id_government_institution = $data['invitations'][$i]['id_government_institution'];
                }
            }

            $invitation->save();

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
            'expert_bank_team_id' => null,
            'project_name' => $project->project_title,
            'invitations' => [],
            'file' => null,
            'invitation_file' => null,
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
                        $institution = $i->feasibilityTestTeamMember->expertBank->institution;
                    } else if($i->feasibilityTestTeamMember->id_luk_member) {
                        $name = $i->feasibilityTestTeamMember->lukMember->name;
                        $email = $i->feasibilityTestTeamMember->lukMember->email;
                        $institution = $i->feasibilityTestTeamMember->lukMember->institution;
                    }

                    if($i->feasibilityTestTeamMember->position == 'Ketua') {
                        $type_member = 'Ketua TUK';
                    } else {
                        $type_member = 'Anggota TUK';
                    }

                   $invitations[] = [
                        'id' => $i->id,
                        'role' => $type_member,
                        'name' => $name,
                        'email' => $email,
                        'type' => $type_member,
                        'type_member' => $type_member,
                        'institution' => $institution,
                        'tuk_member_id' => $i->id_feasibility_test_team_member,
                    ];
                } else if($i->id_tuk_secretary_member) {
                    $invitations[] = [
                        'id' => $i->id,
                        'role' => 'Anggota Sekretariat TUK',
                        'name' => $i->tukSecretaryMember->name,
                        'email' => $i->tukSecretaryMember->email,
                        'type' => 'Anggota Sekretariat TUK',
                        'type_member' => 'Anggota Sekretariat TUK',
                        'institution' => $i->tukSecretaryMember->institution,
                        'tuk_member_id' => $i->id_tuk_secretary_member,
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
            'initiator_name' => $meeting->project->initiator->name,
            'id_initiator' => $meeting->project->initiator->id,
            'meeting_date' => $meeting->meeting_date,
            'meeting_time' => $meeting->meeting_time,
            'person_responsible' => $meeting->project->initiator->pic,
            'location' => $meeting->location,
            'expert_bank_team_id' => $meeting->expert_bank_team_id,
            'project_name' => $meeting->project->project_title,
            'invitations' => $invitations,
            'file' => $meeting->file,
            'invitation_file' => $meeting->invitation_file,
            'deleted_invitations' => []
        ];

        return $data;
    }

    private function getTukMember($id)
    {
        $project = Project::findOrFail($id);
        $newMembers = [];
        $tuk = null;

        if(strtolower($project->authority) == 'pusat' || $project->authority == null) {
            $tuk = FeasibilityTestTeam::where('authority', 'Pusat')->first();
        } else if((strtolower($project->authority) === 'provinsi') && ($project->auth_province !== null)) {
            $tuk = FeasibilityTestTeam::where([['authority', 'Provinsi'],['id_province_name', $project->auth_province]])->first();
        } else if((strtolower($project->authority) == 'kabupaten') && ($project->auth_district !== null)) {
            $tuk = FeasibilityTestTeam::where([['authority', 'Kabupaten/Kota'],['id_district_name', $project->auth_district]])->first();
        }

        if($tuk) {
            $members = FeasibilityTestTeamMember::where('id_feasibility_test_team', $tuk->id)->get();
            
            foreach($members as $m) {
                $name = '';
                $email = '';
                $type_member = '';
                $institution = '';
    
                if($m->expertBank) {
                    $name = $m->expertBank->name;
                    $email = $m->expertBank->email;
                    $institution = $m->expertBank->institution;
                    $type_member = 'expert';
                } else if($m->lukMember) {
                    $name = $m->lukMember->name;
                    $email = $m->lukMember->email;
                    $institution = $m->lukMember->institution;
                    $type_member = 'employee';
                }
    
                $newMembers[] = [
                    'id' => $m->id,
                    'role' => $m->position,
                    'name' => $name,
                    'email' => $email,
                    'institution' => $institution,
                    'type' => 'tuk',
                    'type_member' => $type_member
                ];
            }

            // === SECRETARY MEMBER === //
            $secretary = TukSecretaryMember::where('id_feasibility_test_team', $tuk->id)->get();
            foreach($secretary as $s) {
                $newMembers[] = [
                    'id' => $s->id,
                    'role' => 'Anggota Sekretariat',
                    'name' => $s->name,
                    'email' => $s->email,
                    'institution' => $s->institution,
                    'type' => 'tuk_secretary',
                    'type_member' => 'secretary-member'
                ];
            }
        }

        return $newMembers;
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

        // === PETA === //
        $checkPeta = $this->checkPeta($id_project, $document_type);
        
        // === KONSULTASI PUBLIK === //
        $checkKonsulPublik = $this->checkKonsulPublik($id_project);

        // === PENYUSUN === //
        $checkCvPenyusun = $this->checkCvPenyusun($id_project);

        // === TUK === // 
        $tuk = null;
        $kepala_sekretariat_tuk = '';
        $authority = '';
        $authority_big = '';
        $tuk_address = '';
        $tuk_telp = '';
        $tuk_logo = null;
        $institution_name = '';

        if(strtolower($project->authority) == 'pusat' || $project->authority == null) {
            $tuk = FeasibilityTestTeam::where('authority', 'Pusat')->first();
            $authority = 'Pusat';
            $authority_big = 'PUSAT';
        } else if((strtolower($project->authority) === 'provinsi') && ($project->auth_province !== null)) {
            $tuk = FeasibilityTestTeam::where([['authority', 'Provinsi'],['id_province_name', $project->auth_province]])->first();
            if($tuk) {
                $authority = ucwords(strtolower('PROVINSI ' . strtoupper($tuk->provinceAuthority->name)));
                $authority_big = 'PROVINSI ' . strtoupper($tuk->provinceAuthority->name);
            }
        } else if((strtolower($project->authority) == 'kabupaten') && ($project->auth_district !== null)) {
            $tuk = FeasibilityTestTeam::where([['authority', 'Kabupaten/Kota'],['id_district_name', $project->auth_district]])->first();
            if($tuk) {
                $authority = ucwords(strtolower(strtoupper($tuk->districtAuthority->name)));
                $authority_big = strtoupper($tuk->districtAuthority->name);
            }
        }
        
        if($tuk) {
            $tuk_address = $tuk->address;
            $tuk_telp = $tuk->phone;
            $kepala_sekretariat = FeasibilityTestTeamMember::where([['id_feasibility_test_team', $tuk->id],['position', 'Kepala Sekretariat']])->first();
            if($kepala_sekretariat) {
                if($kepala_sekretariat->expertBank) {
                    $kepala_sekretariat_tuk = $kepala_sekretariat->expertBank->name;
                } else if($kepala_sekretariat->lukMember) {
                    $kepala_sekretariat_tuk = $kepala_sekretariat->lukMember->name;
                }
            }
            $tuk_logo = $tuk->logo;

            if($tuk->institution) {
                $institution_name = strtoupper($tuk->institution);
            }
        }

         if($document_type == 'rkl-rpl') {
             if($authority_big == 'PUSAT') {
                 $templateProcessor = new TemplateProcessor('template_berkas_adm_ar_yes.docx');
             } else {
                 $templateProcessor = new TemplateProcessor('template_berkas_adm_ar_yes_tuk.docx');
                 $templateProcessor->setValue('tuk_address', $tuk_address);
                 $templateProcessor->setValue('tuk_telp', $tuk_telp);
                 $templateProcessor->setValue('authority_big', $authority_big);
                 $templateProcessor->setValue('authority_location', str_replace('Provinsi', '', $authority));

                 if($tuk_logo) {
                    $templateProcessor->setImageValue('logo_tuk', substr(str_replace('//', '/', $tuk_logo), 1));
                } else {
                    $templateProcessor->setImageValue('logo_tuk', 'images/logo-klhk-doc.jpg');
                }
             }
         } else {
             if($authority_big == 'PUSAT') {
                 $templateProcessor = new TemplateProcessor('template_berkas_adm_uu_yes.docx');
             } else {
                $templateProcessor = new TemplateProcessor('template_berkas_adm_uu_yes_tuk.docx');
                $templateProcessor->setValue('tuk_address', $tuk_address);
                $templateProcessor->setValue('tuk_telp', $tuk_telp);
                $templateProcessor->setValue('authority_big', $authority_big);
                $templateProcessor->setValue('authority_location', str_replace('Provinsi', '', $authority));

                if($tuk_logo) {
                   $templateProcessor->setImageValue('logo_tuk', substr(str_replace('//', '/', $tuk_logo), 1));
               } else {
                   $templateProcessor->setImageValue('logo_tuk', 'images/logo-klhk-doc.jpg');
               }
            }
         }

        if($authority_big !== 'PUSAT') {
            $templateProcessor->setValue('institution_name', $institution_name);
        } 

        $templateProcessor->setValue('authority', $authority);
        $templateProcessor->setValue('project_title', $project->project_title);
        $templateProcessor->setValue('pemrakarsa', $project->initiator->name);
        $templateProcessor->setValue('project_description', $project->description);
        $templateProcessor->setValue('project_location', $project_address);
        $templateProcessor->setValue('meeting_time', $meeting_time . ' ' . $meeting_date);
        $templateProcessor->setValue('docs_date', $docs_date);
        $templateProcessor->setValue('kepala_sekretariat_tuk', $kepala_sekretariat_tuk);
        $templateProcessor->setValue('validator_administrasi', Auth::user()->name);
        $templateProcessor->setValue('tim_penyusun', $tim_penyusun);
        $templateProcessor->cloneBlock('anggota_penyusun', count($anggota_penyusun), true, false, $anggota_penyusun);
        $templateProcessor->cloneBlock('meeting_invitations', count($meeting_invitations), true, false, $meeting_invitations);

        if($verification->forms) {
            if($verification->forms->first()) {
                foreach($verification->forms as $f) {
                    if(!($document_type == 'ukl-upl' && $f->name == 'peta_titik')) {
                        $templateProcessor->setValue($f->name . '_exist', 
                                    $this->checkFileAdmExist(
                                                    'exist', 
                                                    $f->name, 
                                                    $project, 
                                                    $checkPeta, 
                                                    $checkKonsulPublik, 
                                                    $checkCvPenyusun));
                        $templateProcessor->setValue($f->name . '_not_exist', 
                        $this->checkFileAdmExist(
                                        'not_exist', 
                                        $f->name, 
                                        $project, 
                                        $checkPeta, 
                                        $checkKonsulPublik, 
                                        $checkCvPenyusun));
                        $templateProcessor->setValue($f->name . '_yes', $f->suitability == 'Sesuai' ? 'V' : '');
                        $templateProcessor->setValue($f->name . '_no', $f->suitability == 'Tidak Sesuai' ? 'V' : '');
                        $templateProcessor->setValue($f->name . '_ket', $f->description);
                    }
                }
            }
        }

        $notesTable = new Table();
        $notesTable->addRow();
        $cell = $notesTable->addCell(6000);
        $final_notes = $verification->notes;
        if($final_notes) {
            $final_notes = str_replace('<p>', '<p style="font-family: tahoma; font-size: 11px;">', $final_notes);
        }
        Html::addHtml($cell, $final_notes);

        $templateProcessor->setComplexBlock('notes', $notesTable);
        
        if($document_type == 'ukl-upl') {
            $templateProcessor->saveAs(storage_path('app/public/adm/berkas-adm-uu-' . strtolower(str_replace('/', '-', $project->project_title)) . '.docx'));
        } else {
            $templateProcessor->saveAs(storage_path('app/public/adm/berkas-adm-ar-' . strtolower(str_replace('/', '-', $project->project_title)) . '.docx'));
        }

        return strtolower(str_replace('/', '-', $project->project_title));
    }

    private function meetingInvitation($id_project, $document_type)
    {
        if (!File::exists(storage_path('app/public/meet-inv/'))) {
            File::makeDirectory(storage_path('app/public/meet-inv/'));
        }

        $project = Project::findOrFail($id_project);
        $testing_meeting = TestingMeeting::select('id', 'id_project', 'updated_at', 'location', 'meeting_date', 'meeting_time')->where([['id_project', $id_project],['document_type', $document_type]])->first();
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
        $tuk_logo = null;
        $ketua_tuk_name = '';
        $ketua_tuk_nip = '';
        $institution_name = '';

        if(strtolower($project->authority) == 'pusat' || $project->authority == null) {
            $tuk = FeasibilityTestTeam::where('authority', 'Pusat')->first();
            $authority = 'Pusat';
            $authority_big = 'PUSAT';
            $authority_big_check = 'PUSAT';
        } else if((strtolower($project->authority) === 'provinsi') && ($project->auth_province !== null)) {
            $tuk = FeasibilityTestTeam::where([['authority', 'Provinsi'],['id_province_name', $project->auth_province]])->first();
            if($tuk) {
                $authority = ucwords(strtolower('PROVINSI ' . strtoupper($tuk->provinceAuthority->name)));
                $authority_big = 'PROVINSI ' . strtoupper($tuk->provinceAuthority->name);
                $authority_big_check = 'PROVINSI ' . strtoupper($tuk->provinceAuthority->name);
            }
        } else if((strtolower($project->authority) == 'kabupaten') && ($project->auth_district !== null)) {
            $tuk = FeasibilityTestTeam::where([['authority', 'Kabupaten/Kota'],['id_district_name', $project->auth_district]])->first();
            if($tuk) {
                $authority = ucwords(strtolower(strtoupper($tuk->districtAuthority->name)));
                $authority_big = strtoupper($tuk->districtAuthority->name);
                $authority_big_check = strtoupper($tuk->districtAuthority->name);
            }
        }
        
        if($tuk) {
            $tuk_address = $tuk->address;
            $ketua = FeasibilityTestTeamMember::where([['id_feasibility_test_team', $tuk->id],['position', 'Ketua']])->first();
            if($ketua) {
                if($ketua->expertBank) {
                    $ketua_tuk_name = $ketua->expertBank->name;
                } else if($ketua->lukMember) {
                    $ketua_tuk_name = $ketua->lukMember->name;
                    $ketua_tuk_nip = $ketua->lukMember->nip ? 'NIP. ' . $ketua->lukMember->nip : '';
                }
            }
            $tuk_logo = $tuk->logo;

            if($tuk->institution) {
                $institution_name = strtoupper($tuk->institution);
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
            } else if($i->id_tuk_secretary_member) {
                $secretary = TukSecretaryMember::find($i->id_tuk_secretary_member);
                if($secretary) {
                    $member[] = [
                        'name' => count($member) + 1 . '. ' . $secretary->name . ' (Anggota Sekretariat)'
                    ];
                }
            } else if($i->role == 'Tenaga Ahli') {
                $ahli[] = [
                    'name' => count($ahli) + 1 . '. ' . $i->name
                ];
            }  else {
                if($i->id_government_institution) {
                    $institution = GovernmentInstitution::find($i->id_government_institution);
                    if($institution) {
                        $instansi[] = [
                            'name' => count($instansi) + 1 . '. Wakil dari ' . $institution->name
                        ];
                    } 
                } else {
                    $instansi[] = [
                        'name' => count($instansi) + 1 . '. Wakil dari ' . $i->institution
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

        $templateProcessor->setValue('institution_name', $institution_name);
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
            $templateProcessor->saveAs(storage_path('app/public/meet-inv/ukl-upl-' . strtolower(str_replace('/', '-', $project->project_title)) . '.docx'));
        } else {
            $templateProcessor->saveAs(storage_path('app/public/meet-inv/andal-rkl-rpl-' . strtolower(str_replace('/', '-', $project->project_title)) . '.docx'));
        }

        return strtolower(str_replace('/', '-', $project->project_title));
    }

    private function checkFileAdmExist($is_exist, $type, $project, $checkPeta, $checkKonsulPublik, $checkCvPenyusun)
    {
        if($type == 'tata_ruang') {
            if($project->ktr) {
                if($is_exist == 'exist') {
                    return 'V';
                } else {
                    return '';
                }
            } else {
                if($is_exist == 'exist') {
                    return '';
                } else {
                    return 'V';
                }
            }
        } else if($type == 'pippib') {
            if($is_exist == 'exist') {
                return 'V';
            } else {
                return '';
            }
        } else if($type == 'persetujuan_awal') {
            if($project->pre_agreement_file) {
                if($is_exist == 'exist') {
                    return 'V';
                } else {
                    return '';
                }
            } else {
                if($is_exist == 'exist') {
                    return '';
                } else {
                    return 'V';
                }
            }
        } else if($type == 'surat_penyusun') {
            if($is_exist == 'exist') {
                return 'V';
            } else {
                return '';
            }
        } else if($type == 'sertifikasi_penyusun') {
            if($is_exist == 'exist') {
                return 'V';
            } else {
                return '';
            }
        } else if($type == 'peta') {
            if($checkPeta) {
                if($is_exist == 'exist') {
                    return 'V';
                } else {
                    return '';
                }
            } else {
                if($is_exist == 'exist') {
                    return 'V';
                } else {
                    return '';
                }
            }
        } else if($type == 'konsul_publik') {
            if($checkKonsulPublik) {
                if($is_exist == 'exist') {
                    return 'V';
                } else {
                    return '';
                }
            } else {
                if($is_exist == 'exist') {
                    return '';
                } else {
                    return 'V';
                }
            }
        } else if($type == 'cv_penyusun') {
            if($checkCvPenyusun) {
                if($is_exist == 'exist') {
                    return 'V';
                } else {
                    return '';
                }
            } else {
                if($is_exist == 'exist') {
                    return '';
                } else {
                    return 'V';
                }
            }
        } else if($type == 'sistematika_penyusunan') {
            if($is_exist == 'exist') {
                return 'V';
            } else {
                return '';
            }
        } else if($type == 'pertek') {
            $env_manage_docs = EnvManageDoc::where([['id_project', $project->id],['type', 'DPT']])->count();
            if($is_exist == 'exist') {
                return $env_manage_docs > 0 ? 'V' : '';
            } else {
                return $env_manage_docs === 0 ? 'V' : '';
            }
        } else if($type == 'peta_titik') {
            $maps = ProjectMapAttachment::where('id_project', $project->id)->whereIn('attachment_type', ['pengelolaan', 'pemantauan'])->count();
            if($is_exist == 'exist') {
                return $maps > 0 ? 'V' : '';
            } else {
                return $maps == 0 ? 'V' : '';
            }
         }

        return '';
    }

    private function checkPeta($id_project, $document_type)
    {
        $total = 0;
        $maps = ProjectMapAttachment::where('id_project', $id_project)->get();
        foreach($maps as $m) {
            if($m->attachment_type == 'tapak') {
                $total++;
            } else if($m->attachment_type == 'ecology') {
                $total++;
            } else if($m->attachment_type == 'social') {
                $total++;
            } else if($m->attachment_type == 'study') {
                $total++;
            }

            if($document_type == 'ukl-upl') {
                if($m->attachment_type == 'pengelolaan' || $m->attachment_type == 'pemantauan') {
                    $total++;
                }
            }
        }

        if($total > 0) {
            return true;
        }

        return false;
    }

    private function checkKonsulPublik($id_project)
    {
        $konsul = PublicConsultation::where('project_id', $id_project)->count();
        if($konsul > 0) {
            return true;
        } else {
            return false;
        }
    }

    private function checkCvPenyusun($id_project)
    {
        $cv_penyusun = 0;

        $team = FormulatorTeam::where('id_project', $id_project)->first();
        if($team) {
            $members = FormulatorTeamMember::where('id_formulator_team', $team->id)->get();
            foreach($members as $m) {
                if($m->formulator) {
                    if($m->formulator->cv_file) {
                        $cv_penyusun++;
                    }
                } else if($m->expert) {
                    if($m->expert->cv) {
                        $cv_penyusun++;
                    }
                }
            }
        }

        if($cv_penyusun > 0) {
            return true;
        } else {
            return false;
        }
    }

    private function addToTukProject($id_project, $id_member, $type)
    {
        $column = null;
        if($type == 'member') {
            $column = 'id_feasibility_test_team_member';
        } else if($type == 'secretary') {
            $column = 'id_tuk_secretary_member';
        }

        $tuk_project_count = TukProject::where([['id_project', $id_project],[$column, $id_member]])->count();
        if($tuk_project_count === 0) {
            $tuk_project = new TukProject();
            $tuk_project->id_project = $id_project;

            if($type == 'member') {
                $tuk_project->id_feasibility_test_team_member = $id_member;
            } else if($type == 'secretary') {
                $tuk_project->id_tuk_secretary_member = $id_member;
            }

            $tuk_project->role = 'valsub';
            $tuk_project->save();
        }
    }
}
