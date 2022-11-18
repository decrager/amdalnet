<?php

namespace App\Entity;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use ZeroDaHero\LaravelWorkflow\Traits\WorkflowTrait;
use OwenIt\Auditing\Contracts\Auditable;
// use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Illuminate\Support\Facades\Auth;

class Project extends Model implements Auditable
{
    use AuditableTrait;

    use SoftDeletes;

    use WorkflowTrait;

    // protected $fillable = [
    //     'project_title',
    //     'scale',
    //     'scale_unit',
    //     'authority',
    //     'project_type',
    //     'sector',
    //     'description',
    //     'id_applicant',
    //     'id_prov',
    //     'id_district',
    //     'address',
    //     'field',
    //     'location_desc',
    //     'risk_level',
    //     'project_year',
    //     'map',
    //     'map_scale',
    //     'map_scale_unit',
    //     'id_formulator_team',
    //     'announcement_letter',
    //     'kbli',
    //     'result_risk',
    //     'required_doc',
    //     'biz_type',
    //     'id_project',
    //     'type_formulator_team',
    //     'ktr',
    //     'id_lpjp',
    //     'pre_agreement',
    //     'pre_agreement_file',
    // ];

    protected $guarded = [];

    protected $appends = ['filling_date', 'submission_deadline', 'rkl_rpl_document', 'ukl_upl_document', 'form_ka_doc'];

    public function team()
    {
        return $this->hasOne(FormulatorTeam::class, 'id_project', 'id');
    }

    public function impactIdentifications()
    {
        return $this->hasMany(ImpactIdentification::class, 'id_project', 'id');
    }

    public function impactIdentificationsClone()
    {
        return $this->hasMany(ImpactIdentificationClone::class, 'id_project', 'id');
    }

    public function address()
    {
        return $this->hasMany(ProjectAddress::class, 'id_project', 'id');
    }

    public function authorities()
    {
        return $this->hasMany(ProjectAuthority::class, 'id_project', 'id');
    }

    public function listSubProject()
    {
        return $this->hasMany(SubProject::class, 'id_project', 'id');
    }

    public function testingMeeting()
    {
        return $this->hasMany(TestingMeeting::class, 'id_project', 'id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'id_district', 'id');
    }

    public function province()
    {
        return $this->hasOne(Province::class, 'id', 'id_prov');
    }

    public function initiator()
    {
        return $this->belongsTo(Initiator::class, 'id_applicant', 'id');
    }

    public function subProjects()
    {
        return $this->hasMany(SubProject::class, 'id_project', 'id');
    }

    public function mapFiles()
    {
        return $this->hasMany(ProjectMapAttachment::class, 'id_project', 'id');
    }

    public function announcement()
    {
        return $this->hasOne(Announcement::class, 'project_id', 'id');
    }

    public function feasibilityTest()
    {
        return $this->hasMany(FeasibilityTest::class, 'id_project', 'id');
    }

    public function kaReviews()
    {
        return $this->hasMany(KaReview::class, 'id_project', 'id');
    }

    public function skkl()
    {
        return $this->hasOne(ProjectSkkl::class, 'id_project', 'id');
    }

    public function meetingReports()
    {
        return $this->hasMany(MeetingReport::class, 'id_project', 'id');
    }

    public function tukProject()
    {
        return $this->hasMany(TukProject::class, 'id_project', 'id');
    }

    public function feasibilityTestRecap()
    {
        return $this->hasOne(FeasibilityTestRecap::class, 'id_project', 'id');
    }

    public function DocumentAttachment()
    {
        return $this->hasMany(DocumentAttachment::class, 'id_project', 'id');
    }

    public function projectFilter()
    {
        return $this->hasMany(ProjectFilter::class, 'id_project', 'id');
    }

    public function getFillingDateAttribute()
    {
        Carbon::setLocale('id');
        return Carbon::createFromFormat('Y-m-d H:i:s', $this->created_at)->isoFormat('D MMMM Y');
    }

    public function getFormKaDocAttribute()
    {
        $formKa = DocumentAttachment::where('id_project', $this->id)->first();
        if($formKa != null) {
            return true;
        }

        return false;
    }

    public function getSubmissionDeadlineAttribute()
    {
        Carbon::setLocale('id');
        return Carbon::createFromFormat('Y-m-d H:i:s', $this->created_at)->addMonth(1)->isoFormat('D MMMM Y');
    }

    public function getRklRplDocumentAttribute()
    {
        if (!Storage::disk('public')->exists('workspace')) {
            return false;
        }

        $save_file_name = $this->id . '-rkl-rpl' . '.docx';

        if (Storage::disk('public')->exists('workspace/' . $save_file_name)) {
            return true;
        }

        return false;
    }

    public function getUklUplDocumentAttribute()
    {
        if (!Storage::disk('public')->exists('workspace')) {
            return false;
        }

        $save_file_name = 'ukl-upl-' . strtolower(str_replace('/', '-', $this->project_title)) . '.docx';

        if (Storage::disk('public')->exists('workspace/' . $save_file_name)) {
            return true;
        }

        return false;
    }

    public function getKtrAttribute()
    {
        if($this->attributes['ktr']) {
            if(str_contains($this->attributes['ktr'], 'storage/')) {
                return $this->attributes['ktr'];
            } else {
                // return Storage::url($this->attributes['ktr']);
                return Storage::disk('public')->temporaryUrl($this->attributes['ktr'], now()->addMinutes(env('TEMPORARY_URL_TIMEOUT')));
            }
        }

        return null;
    }

    public function getPreAgreementFileAttribute()
    {
        if($this->attributes['pre_agreement_file']) {
            if(str_contains($this->attributes['pre_agreement_file'], 'storage/')) {
                return $this->attributes['pre_agreement_file'];
            } else {
                // return Storage::url($this->attributes['pre_agreement_file']);
                return Storage::disk('public')->temporaryUrl($this->attributes['pre_agreement_file'], now()->addMinutes(env('TEMPORARY_URL_TIMEOUT')));
            }
        }

        return null;
    }

    public function getKawasanLindungFileAttribute()
    {
        if($this->attributes['kawasan_lindung_file']) {
            if(str_contains($this->attributes['kawasan_lindung_file'], 'storage/')) {
                return $this->attributes['kawasan_lindung_file'];
            } else {
                // return Storage::url($this->attributes['kawasan_lindung_file']);
                return Storage::disk('public')->temporaryUrl($this->attributes['kawasan_lindung_file'], now()->addMinutes(env('TEMPORARY_URL_TIMEOUT')));
            }
        }

        return null;
    }

    public function getPpibFileAttribute()
    {
        if($this->attributes['ppib_file']) {
            if(str_contains($this->attributes['ppib_file'], 'storage/')) {
                return $this->attributes['ppib_file'];
            } else {
                // return Storage::url($this->attributes['ppib_file']);
                return Storage::disk('public')->temporaryUrl($this->attributes['ppib_file'], now()->addMinutes(env('TEMPORARY_URL_TIMEOUT')));
            }
        }

        return null;
    }

    public function getMapAttribute()
    {
        if($this->attributes['map']) {
            if(str_contains($this->attributes['map'], 'storage/')) {
                return $this->attributes['map'];
            } else {
                // return Storage::url($this->attributes['map']);
                return Storage::disk('public')->temporaryUrl($this->attributes['map'], now()->addMinutes(env('TEMPORARY_URL_TIMEOUT')));
            }
        }

        return null;
    }

    public function rawKtr()
    {
        return $this->attributes['ktr'];
    }

    public function rawPreAgreementFile()
    {
        return $this->attributes['pre_agreement_file'];
    }

    public function getOssSpplDocAttribute()
    {
        if(isset($this->attributes['oss_sppl_doc']) && $this->attributes['oss_sppl_doc'] != null) {
            return Storage::disk('public')->temporaryUrl($this->attributes['oss_sppl_doc'], now()->addMinutes(env('TEMPORARY_URL_TIMEOUT')));
        }

        return null;
    }

    public function getOssRequiredDocAttribute()
    {
        if(isset($this->attributes['oss_required_doc']) && $this->attributes['oss_required_doc'] != null) {
            return Storage::disk('public')->temporaryUrl($this->attributes['oss_required_doc'], now()->addMinutes(env('TEMPORARY_URL_TIMEOUT')));
        }

        return null;
    }

    public function applyWorkFlowTransition($transition, $fromState, $endState, $changeMarking = true){
        $userId = Auth::user()->id;
        $wflog = WorkflowLog::create([
            'id_project' => $this->attributes['id'],
            'transition' => $transition,
            'from_place' => $fromState,
            'to_place' => $endState,
            'duration' => 0,
            'duration_total' => 0,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
        if($changeMarking){
            $this->attributes['marking'] = $endState;
            $this->save();
        }
        return null;
    }
}
