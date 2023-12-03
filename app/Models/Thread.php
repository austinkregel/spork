<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Thread extends Model implements Crud
{
    use HasFactory;

    public $casts = ['settings' => 'json', 'origin_server_ts' => 'datetime'];

    public $appends = ['human_timestamp'];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function participants()
    {
        return $this->belongsToMany(Person::class, 'thread_participants');
    }

    public function getHumanTimestampAttribute()
    {
        return $this->origin_server_ts->diffForHumans(now(), CarbonInterface::DIFF_RELATIVE_TO_NOW, false);
    }
}
