<?php

namespace App\Entity;

use Illuminate\Database\Eloquent\Model;
use App\Entity\Feedback;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Workflow\Workflow;
use Illuminate\Support\Facades\Auth;

class Announcement extends Model
{
    protected $fillable = [
        'pic_name',
        'pic_address',
        'cs_name',
        'cs_address',
        'project_type',
        'project_location',
        'project_scale',
        'proof',
        'potential_impact',
        'start_date',
        'end_date',
        'project_id',
        'project_result',
        'id_applicant',
        'is_done',
    ];

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function initiator()
    {
        return $this->belongsTo(Initiator::class, 'id_applicant', 'id');
    }

    protected static function boot()
    {
        parent::boot();
        static::created(function ($announcement) {
            $project = Project::find($announcement->project_id);
            if($project){
                switch ($project->marking){
                    case 'formulator-assignment':
                        $project->workflow_apply('draft-announcement');
                        $project->save();
                        break;
                    case 'screening-completed':
                        $project->applyWorkFlowTransition('draft-announcement', 'formulator-assignment', 'announcement-drafting');
                        break;
                    default:
                }
            }
        });
    }

    public function getProofAttribute()
    {
        if($this->attributes['proof']) {
            if(str_contains($this->attributes['proof'], 'storage/')) {
                return $this->attributes['proof'];
            } else {
                // return Storage::url($this->attributes['proof']);
                return Storage::disk('public')->temporaryUrl($this->attributes['proof'], now()->addMinutes(env('TEMPORARY_URL_TIMEOUT')));
            }
        }

        return null;
    }

    public function rawProof(){
        // $arrFile = explode('/', $this->attributes['proof']);
        // return implode(DIRECTORY_SEPARATOR, $arrFile);
        return $this->attributes['proof'];
    }
}
