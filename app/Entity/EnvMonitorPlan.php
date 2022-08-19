<?php

namespace App\Entity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnvMonitorPlan extends Model
{
    use HasFactory;

    protected $table = 'env_monitor_plans';

    protected $fillable = [
        'id_impact_identifications',
        'form',
        'location',
        'period',
        'executor',
        'supervisor',
        'report_recipient',
        'description',
    ];

    public function impactIdentification()
    {
        return $this->belongsTo(ImpactIdentificationClone::class, 'id_impact_identifications', 'id');
    }

    public function sources()
    {
        return $this->hasMany(EnvPlanSource::class, 'id_env_monitor_plan', 'id');
    }

    public function indicators()
    {
        return $this->hasMany(EnvPlanIndicator::class, 'id_env_monitor_plan', 'id');
    }

    public function locations()
    {
        return $this->hasMany(EnvPlanLocation::class, 'id_env_monitor_plan', 'id');
    }

    public function forms()
    {
        return $this->hasMany(EnvPlanForm::class, 'id_env_monitor_plan', 'id');
    }
}
