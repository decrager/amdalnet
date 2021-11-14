<?php

namespace App\Entity;

use Illuminate\Database\Eloquent\Model;
use App\Entity\Feedback;

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
    ];

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    public function project(){
        return $this->hasOne(Project::class, 'id', 'project_id');
    }

    public function province(){
        return $this->hasOne(Province::class, 'id', 'id_prov');
    }
}
