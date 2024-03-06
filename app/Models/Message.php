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
use Spatie\Tags\HasTags;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

/**
 * @property-read Credential $credential
 */
/** @mixin \Eloquent */
class Message extends Model implements Taggable
{
    use HasFactory, HasJsonRelationships, HasTags;

    public $fillable = [
        'from_person',
        'to_person',
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

    public function credential()
    {
        return $this->belongsTo(Credential::class);
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

    public function broadcastWith(string $event): array
    {
        $data = $this->toArray();
        unset($data['html_message']);
        unset($data['message']);

        return $data;
    }
}
