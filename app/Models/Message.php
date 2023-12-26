<?php

namespace App\Models;

use App\Events\Models\Message\MessageCreated;
use App\Events\Models\Message\MessageCreating;
use App\Events\Models\Message\MessageDeleted;
use App\Events\Models\Message\MessageDeleting;
use App\Events\Models\Message\MessageUpdated;
use App\Events\Models\Message\MessageUpdating;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    public $appends = ['is_user'];

    public $dispatchesEvents = [
        'created' => MessageCreated::class,
        'creating' => MessageCreating::class,
        'deleting' => MessageDeleting::class,
        'deleted' => MessageDeleted::class,
        'updating' => MessageUpdating::class,
        'updated' => MessageUpdated::class,
    ];

    public function getIsUserAttribute()
    {
        return auth()->id() === $this->from_person;
    }
}
