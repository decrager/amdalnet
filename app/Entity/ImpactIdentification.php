<?php

namespace App\Entity;

use Illuminate\Database\Eloquent\Model;

class ImpactIdentification extends Model
{
    protected $table = 'impact_identifications';
    protected $fillable = [
        'id_project',
        'id_project_component',
        'id_project_rona_awal',
        'id_change_type',
        'id_unit',
        'nominal',
    ];

    public function project(){
        return $this->belongsTo(Project::class, 'id_project');
    }

    public function component(){
        return $this->belongsTo(ProjectComponent::class, 'id_project_component');
    }

    public function ronaAwal(){
        return $this->belongsTo(ProjectRonaAwal::class, 'id_project_rona_awal');
    }

    public function changeType(){
        return $this->belongsTo(ChangeType::class, 'id_change_type');
    }

    public function unit(){
        return $this->belongsTo(Unit::class, 'id_unit');
    }

    public function envImpactAnalysis()
    {
        return $this->hasOne(EnvImpactAnalysis::class, 'id_impact_identifications', 'id');
    }

}
