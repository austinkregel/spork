<?php

declare(strict_types=1);

namespace App\Models;

use App\Events\Models\Message\MessageCreated;
use App\Events\Models\Message\MessageCreating;
use App\Events\Models\Message\MessageDeleted;
use App\Events\Models\Message\MessageDeleting;
use App\Events\Models\Message\MessageUpdated;
use App\Events\Models\Message\MessageUpdating;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

/** @mixin \Eloquent */
class Message extends Model
{
    use HasFactory, HasJsonRelationships;

    public $fillable = [
        'from_person',
        'to_email',
        'from_email',
        'thread_id',
        'type',
        'event_id',
        'originated_at',
        'thumbnail_url',
        'is_decrypted',
        'message',
        'html_message',
        'settings',
        'seen',
        'spam',
        'answered',
        'subject',
    ];

    public $casts = [
        'seen' => 'bool',
        'spam' => 'bool',
        'answered' => 'bool',
        'originated_at' => 'timestamp',
        'settings' => 'json',
    ];

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

    public function fromPerson()
    {
        return $this->belongsTo(Person::class, 'from_person');
    }

    public function from()
    {
        return $this->hasManyJson(Person::class, 'emails', 'from_email');
    }

    public function to()
    {
        return $this->hasManyJson(Person::class, 'emails', 'to_email');
    }
}
