<?php

namespace App\Entity;

use Illuminate\Database\Eloquent\Model;
use App\Entity\Announcement;

class Feedback extends Model
{
    protected $table = 'feedbacks';
    protected $fillable = [
        'announcement_id',
        'name',
        'id_card_number',
        'phone',
        'email',
        'concern',
        'expectation',
        'rating',
        'photo_filepath',
        'responder_type_id',
        'is_relevant',
        'set_relevant_by',
        'set_relevant_at',
        'deleted',
        'deleted_at',
        'environment_condition',
        'local_impact',
        'community_type',
        'community_gender'
    ];

    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }
}
