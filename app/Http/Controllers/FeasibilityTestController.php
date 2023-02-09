<?php

namespace App\Http\Controllers;

use App\Entity\DocumentAttachment;
use App\Entity\EligibilityCriteria;
use App\Entity\FeasibilityTest;
use App\Entity\FeasibilityTestDetail;
use App\Entity\FeasibilityTestRecap;
use App\Entity\FeasibilityTestTeam;
use App\Entity\FeasibilityTestTeamMember;
use App\Entity\Initiator;
use App\Entity\OssNib;
use App\Entity\Project;
use App\Entity\ProjectAddress;
use App\Entity\TestingMeetingInvitation;
use App\Entity\TukSecretaryMember;
use App\Laravue\Models\User;
use App\Notifications\FeasibilityTestNotification;
use App\Notifications\FeasibilityTestRecapNotification;
use App\Services\OssService;
use App\Utils\TemplateProcessor;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Utils\Document;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Log;
use PDF;

class FeasibilityTestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->checkRecap) {
            $recap = FeasibilityTestRecap::where('id_project', $request->idProject)->first();
            if($recap) {
                return $recap->is_feasib;
            } else {
                return false;
            }
        }

        if($request->finalRecap) {
            return response()->json(FeasibilityTestRecap::where('id_project', $request->idProject)->first());
        }

        if($request->recap) {
            return $this->getRecap($request->idProject);
        }

        if($request->pdf) {
            return $this->exportPDF($request->idProject);
        }

        if($request->dokumen) {
            $document_type = $request->uklUpl ? 'ukl-upl' : 'rkl-rpl';
           return $this->dokumen($request->idProject, $document_type);
        }

        if($request->idProject) {
            $feasibility = FeasibilityTest::where('id_project', $request->idProject)->where(function($q) {
                $q->where(function($qu) {
                    $qu->whereHas('feasibilityTestTeamMember', function($query) {
                        $query->whereHas('lukMember', function($que) {
                            $que->where('email', Auth::user()->email);
                        })->orWhereHas('expertBank', function($que) {
                            $que->where('email', Auth::user()->email);
                        });
                    })->orWhereHas('tukSecretaryMember', function($query) {
                        $query->where('email', Auth::user()->email);
                    });
                })->orWhere('email', Auth::user()->email);
            })->first();
            if($feasibility) {
                return $this->getExistData($feasibility);
            } else {
                return $this->getFreshData($request->idProject);
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
        if($request->recap) {
            $recap = new FeasibilityTestRecap();
            $recap->id_project = $request->idProject;
            $recap->recap = $request->recap;
            $recap->is_feasib = $request->isFeasib;
            $recap->save();

            // if ($request->isOSS === "true") {
            //     OssService::receiveLicenseStatusNotif($request, '45');
            // }

            $project = Project::findOrFail($request->idProject);
            if ($project->is_oss === 1) {
                $initiator = Initiator::find($project->id_applicant);
                if (!$initiator) {
                    Log::error('Initiator not found');
                    return false;
                }
                $ossNib = OssNib::where('nib', $initiator->nib)->first();
                if (!$ossNib) {
                    Log::error('OSSNib not found');
                    return false;
                }
                if ($ossNib) {
                    OssService::receiveLicenseStatusNotif($request, '45');
                }
            }

            $user = User::where('email', $project->initiator->email)->first();

            if($user) {
                Notification::send([$user], new FeasibilityTestRecapNotification($recap));
            }

            return response()->json(['message' => 'Data successfully saved']);
        }

        $data = $request->feasibility;

        $feasibility = null;

        if($data['type'] == 'new') {
            $feasibility = new FeasibilityTest();
            $feasibility->id_project = $data['idProject'];

            // === INPUT USER === //
            $tuk_member = FeasibilityTestTeamMember::whereHas('lukMember', function($query) {
                                $query->where('email', Auth::user()->email);
                            })->orWhereHas('expertBank', function($query) {
                                $query->where('email', Auth::user()->email);
                            })->first();

            if($tuk_member) {
                $feasibility->id_feasibility_test_team_member = $tuk_member->id;
            } else {
                $tuk_secretary = TukSecretaryMember::where('email', Auth::user()->email)->first();
                if($tuk_secretary) {
                    $feasibility->id_tuk_secretary_member = $tuk_secretary->id;
                } else {
                    $feasibility->email = Auth::user()->email;
                }
            }
        } else {
            $feasibility = FeasibilityTest::findOrFail($data['id']);
        }

        $feasibility->conclusion = $data['conclusion'];
        $feasibility->save();

        for($i = 0; $i < count($data['detail']); $i++) {

            $feasibility_detail = null;

            if($data['detail'][$i]['type'] == 'new') {
                $feasibility_detail = new FeasibilityTestDetail();
                $feasibility_detail->id_feasibility_test = $feasibility->id;
                $feasibility_detail->id_eligibility_criteria = $data['detail'][$i]['id'];
            } else {
                $feasibility_detail = FeasibilityTestDetail::where([['id_feasibility_test', $feasibility->id],['id_eligibility_criteria',$data['detail'][$i]['id']]])->first();
            }

            $feasibility_detail->appropriateness = $data['detail'][$i]['appropriateness'];
            $feasibility_detail->notes = $data['detail'][$i]['notes'];
            $feasibility_detail->expert_notes = $data['detail'][$i]['expert_notes'];
            $feasibility_detail->save();
        }

        // === WORKFLOW === //
        $project = Project::findOrFail($data['idProject']);
        if($request->document_type == 'rkl-rpl') {
            if($project->marking == 'amdal.feasibility-ba-signed') {
                $project->workflow_apply('draft-amdal-recommendation');
                $project->workflow_apply('amdal.recommendation-signed');
                $project->save();
            }
        }

        return response()->json(['messsage' => 'success']);
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

    private function getExistData($feasibility) {
        $data = [];

        if($feasibility->detail->first()) {
            foreach($feasibility->detail as $d) {
                $data[] = [
                    'id' => $d->id_eligibility_criteria,
                    'description' => $d->eligibility->description,
                    'appropriateness' => $d->appropriateness,
                    'notes' => $d->notes,
                    'expert_notes' => $d->expert_notes,
                    'type' => 'update'
                ];
            }

            usort($data, function($a, $b) {
                return $a['id'] <=> $b['id'];
            });
        }

        $eligibility = EligibilityCriteria::whereDoesntHave('feasibility', function($q) use($feasibility) {
            $q->where('id_feasibility_test', $feasibility->id);
        });

        foreach($eligibility as $e) {
            $data[] = [
                'id' => $e->id,
                'description' => $e->description,
                'appropriateness' => null,
                'notes' => null,
                'expert_notes' => null,
                'type' => 'new'
            ];
        }

        return [
            'type' => 'update',
            'id' => $feasibility->id,
            'idProject' => $feasibility->id_project,
            'conclusion' => $feasibility->conclusion,
            'detail' => $data
        ];
    }

    private function getFreshData($idProject) {
        $data = [];
        $eligibility = EligibilityCriteria::all();

        foreach($eligibility as $e) {
            $data[] = [
                'id' => $e->id,
                'description' => $e->description,
                'appropriateness' => null,
                'notes' => null,
                'expert_notes' => null,
                'type' => 'new'
            ];
        }

        return [
            'type' => 'new',
            'id' => null,
            'idProject' => $idProject,
            'conclusion' => null,
            'detail' => $data
        ];
    }

    private function dokumen($id_project, $document_type) {
        if (!Storage::disk('public')->exists('uji-kelayakan')) {
            Storage::disk('public')->makeDirectory('uji-kelayakan');
        }

        Carbon::setLocale('id');
        $project = Project::findOrFail($id_project);
        $save_file_name = uniqid() . '-uji-kelayakan-' . strtolower(str_replace('/', '-', $project->project_title)) . '.docx';

        $project_type = $project->project_type;
        $project_title_big = strtoupper($project->project_title);
        $project_address_big = '';
        $project_district_big = '';
        $project_province_big = '';
        $pemrakarsa_big = strtoupper($project->initiator->name);
        $project_title = ucwords(strtolower($project->project_title));
        $project_address = '';
        $project_district = '';
        $project_province = '';
        $pemrakarsa = $project->initiator->name;
        $pic = $project->initiator->pic;
        $pemrakarsa_address = $project->initiator->address;
        $pemrakarsa_position = $project->initiator->pic_role;

        if($project->address) {
            if($project->address->first()) {
                $project_address = $project->address->first()->address;
                $project_district = ucwords(strtolower($project->address->first()->district));
                $project_province = 'Provinsi ' . ucwords(strtolower($project->address->first()->prov));
                $project_address_big = strtoupper($project->address->first()->address);
                $project_district_big = strtoupper($project->address->first()->district);
                $project_province_big = 'PROVINSI ' . strtoupper($project->address->first()->prov);
            }
        }

        // GET TUK
        $tuk = [
            'kepala_sekretariat_tuk_name' => '',
            'kepala_sekretariat_tuk_nip' => '',
            'ketua_tuk_position' => '',
            'ketua_tuk_name' => '',
            'ketua_tuk_nip' => ''
        ];
        $tuk_data = null;
        if(strtolower($project->authority) == 'pusat' || $project->authority == null) {
            $tuk_data = FeasibilityTestTeam::where('authority', 'Pusat')->first();
        } else if((strtolower($project->authority) === 'provinsi') && ($project->auth_province !== null)) {
            $tuk_data = FeasibilityTestTeam::where([['authority', 'Provinsi'],['id_province_name', $project->auth_province]])->first();
        } else if((strtolower($project->authority) == 'kabupaten') && ($project->auth_district !== null)) {
            $tuk_data = FeasibilityTestTeam::where([['authority', 'Kabupaten/Kota'],['id_district_name', $project->auth_district]])->first();
        }

        $tuk = $this->getTukData($tuk_data, $tuk);

        $docs_date = Carbon::createFromFormat('Y-m-d', date('Y-m-d'))->isoFormat('D MMMM Y');

        $templateProcessor = new TemplateProcessor(storage_path('app/public/template/template_kelayakan.docx'));
        if($document_type == 'ukl-upl') {
            $templateProcessor = new TemplateProcessor('template_kelayakan_ukl_upl.docx');
        }

        $templateProcessor->setValue('docs_date', $docs_date);
        $templateProcessor->setValue('project_title_big', $project_title_big);
        $templateProcessor->setValue('project_address_big', $project_address_big);
        $templateProcessor->setValue('project_district_big', $project_district_big);
        $templateProcessor->setValue('project_province_big', $project_province_big);
        $templateProcessor->setValue('pemrakarsa_big', $pemrakarsa_big);
        $templateProcessor->setValue('project_title', $project_title);
        $templateProcessor->setValue('project_address', $project_address);
        $templateProcessor->setValue('project_district', $project_district);
        $templateProcessor->setValue('project_province', $project_province);
        $templateProcessor->setValue('pemrakarsa', $pemrakarsa);
        $templateProcessor->setValue('project_type', $project_type);
        $templateProcessor->setValue('pic', $pic);
        $templateProcessor->setValue('pemrakarsa_address', $pemrakarsa_address);
        $templateProcessor->setValue('pemrakarsa_position', $pemrakarsa_position);
        $templateProcessor->setValue('kepala_sekretariat_tuk_name', $tuk['kepala_sekretariat_tuk_name']);
        $templateProcessor->setValue('kepala_sekretariat_tuk_nip', $tuk['kepala_sekretariat_tuk_nip']);
        $templateProcessor->setValue('ketua_tuk_name', $tuk['ketua_tuk_name']);
        $templateProcessor->setValue('ketua_tuk_position', $tuk['ketua_tuk_position']);
        $templateProcessor->setValue('ketua_tuk_nip', $tuk['ketua_tuk_nip']);

        $document_attachment = DocumentAttachment::where([['id_project', $id_project], ['type', 'Dokumen Uji Kelayakan']])->first();
        if($document_attachment) {
            Storage::disk('public')->delete($document_attachment->rawAttachment());
        }

        // $templateProcessor->saveAs(Storage::disk('public')->path('uji-kelayakan/' . $save_file_name));
        $tmpName = $templateProcessor->save();
        Storage::disk('public')->put('uji-kelayakan/' . $save_file_name, file_get_contents($tmpName));
        unlink($tmpName);

        if(!$document_attachment) {
            // === NOTIFICATION === //
            $pemrakarsa_user = User::where('email', $project->initiator->email)->first();
            if($pemrakarsa_user) {
                Notification::send([$pemrakarsa_user], new FeasibilityTestNotification($project));
            }

            $document_attachment = new DocumentAttachment();
            $document_attachment->id_project = $id_project;
            $document_attachment->type = 'Dokumen Uji Kelayakan';
        }

        $document_attachment->attachment = 'uji-kelayakan/' . $save_file_name;
        $document_attachment->save();

        // WORKFLOW
        if($project->marking == 'amdal.feasibility-ba-signed') {
            $project->workflow_apply('draft-amdal-recommendation');
            $project->workflow_apply('sign-amdal-recommendation');
            $project->save();
        } else if($project->marking == 'uklupl-mt.ba-signed') {
            $project->workflow_apply('draft-uklupl-recommendation');
            $project->workflow_apply('sign-uklupl-recommendation');
            $project->save();
        }

        // get pdf url
        $downloadUri = url($document_attachment->attachment);
        $key = Document::GenerateRevisionId($downloadUri);
        $convertedUri;
        $download_url = Document::GetConvertedUri($downloadUri, 'docx', 'pdf', $key, FALSE, $convertedUri);

        return [
            'docx_url' => $document_attachment->attachment,
            'pdf_url' => $convertedUri
        ];
    }

    private function getTukData($data, $tuk) {
        if($data) {
            $ketua_tuk = FeasibilityTestTeamMember::where([['id_feasibility_test_team', $data->id],['position', 'Ketua']])->first();
            if($ketua_tuk) {
                if($ketua_tuk->expertBank) {
                    $tuk['ketua_tuk_name'] = $ketua_tuk->expertBank->name;
                } else if($ketua_tuk->lukMember) {
                    $tuk['ketua_tuk_position'] = $ketua_tuk->lukMember->position;
                    $tuk['ketua_tuk_name'] = $ketua_tuk->lukMember->name;
                    $tuk['ketua_tuk_nip'] = $ketua_tuk->lukMember->nip ?? '';
                }
            }
            $kepala_sekretariat = FeasibilityTestTeamMember::where([['id_feasibility_test_team', $data->id],['position', 'Kepala Sekretariat']])->first();
            if($kepala_sekretariat) {
                if($kepala_sekretariat->expertBank) {
                    $tuk['kepala_sekretariat_tuk_name'] = $kepala_sekretariat->expertBank->name;
                } else if($kepala_sekretariat->lukMember) {
                    $tuk['kepala_sekretariat_tuk_name'] = $kepala_sekretariat->lukMember->name;
                    $tuk['kepala_sekretariat_tuk_nip'] = $kepala_sekretariat->lukMember->nip ?? '';
                }
            }
        }

        return $tuk;
    }

    private function exportPDF($id_project) {
        $document_attachment = DocumentAttachment::where([['id_project', $id_project],['type', 'Dokumen Uji Kelayakan']])->first();
        $downloadUri = url($document_attachment->attachment);
        $key = Document::GenerateRevisionId($downloadUri);
        $convertedUri;
        $download_url = Document::GetConvertedUri($downloadUri, 'docx', 'pdf', $key, FALSE, $convertedUri);
        return $convertedUri;
    }

    private function getRecap($id_project)
    {
        // === ANGGOTA TUK  === //
        $tuk_member = FeasibilityTestTeamMember::select('id', 'id_luk_member', 'id_expert_bank', 'position')
                        ->whereHas('feasibilityTest', function($q) use($id_project) {
                            $q->where('id_project', $id_project);
                        })->with(['feasibilityTest' => function($q) use($id_project) {
                            $q->where('id_project', $id_project);
                            $q->with('detail.eligibility');
                        }, 'lukMember' => function($q) {
                            $q->select('id', 'name', 'institution');
                        }, 'expertBank' => function($q) {
                            $q->select('id', 'name', 'institution');
                        }])->get();

        // === ANGGOTA SEKRETARIAT TUK === //
        $tuk_secretary_member = TukSecretaryMember::select('id', 'name', 'institution')
                                    ->whereHas('feasibilityTest', function($q) use($id_project) {
                                        $q->where('id_project', $id_project);
                                    })->with(['feasibilityTest' => function($q) use($id_project) {
                                        $q->where('id_project', $id_project);
                                        $q->with('detail.eligibility');
                                    }])->get();

        // === AHLI DARI UNDANGAN === //
        $tuk_invitation = FeasibilityTest::where([['id_project', $id_project],['email', '!=', null]])->with('detail.eligibility')->get();
        $email = [];
        $invitations = [];
        foreach($tuk_invitation as $t) {
            if(!in_array($t->email, $email)) {
                $email[] = $t->email;
            }
        }

        if(count($email) > 0) {
            $invitations = TestingMeetingInvitation::select('id', 'name', 'email', 'institution', 'id_government_institution', 'role')
                            ->whereIn('email', $email)
                            ->whereHas('meeting', function($q) use($id_project) {
                                $q->where('id_project', $id_project);
                            })->with('governmentInstitution')->get();
        }

        return [
            'tuk_member' => $tuk_member,
            'tuk_secretary_member' => $tuk_secretary_member,
            'tuk_invitations' => [
                'invitations' => $invitations,
                'data' => $tuk_invitation
            ]
        ];
    }
}
