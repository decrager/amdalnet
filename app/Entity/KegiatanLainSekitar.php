<?php

namespace App\Entity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KegiatanLainSekitar extends Model
{
    use SoftDeletes;

    // table kegiatan_lain_sekitars
    protected $table = 'kegiatan_lain_sekitars';
    protected $fillable = [
        'name',
        'is_master',
        'originator_id',
    ];

    public function project()
    {
        return $this->hasMany(ProjectKegiatanLainSekitar::class, 'kegiatan_lain_sekitar_id', 'id');
    }

}
