<?php

declare(strict_types=1);

namespace App\Models;

use App\Events\Models\Message\EmailCreated;
use App\Events\Models\Message\EmailCreating;
use App\Events\Models\Message\EmailDeleted;
use App\Events\Models\Message\MessageDeleting;
use App\Events\Models\Message\EmailUpdated;
use App\Events\Models\Message\EmailUpdating;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;
use Spatie\Tags\HasTags;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

/**
 * @property-read Credential $credential
 */
/** @mixin \Eloquent */
class Message extends Model implements Taggable
{
    use HasFactory, HasJsonRelationships, HasTags;
    use Searchable;

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

    public $appends = ['is_user'];

    public $dispatchesEvents = [
        'created' => EmailCreated::class,
        'creating' => EmailCreating::class,
        'deleting' => MessageDeleting::class,
        'deleted' => EmailDeleted::class,
        'updating' => EmailUpdating::class,
        'updated' => EmailUpdated::class,
    ];

    protected function casts(): array
    {
        return [
            'seen' => 'bool',
            'spam' => 'bool',
            'answered' => 'bool',
            'originated_at' => 'timestamp',
            'settings' => 'json',
        ];
    }

    public function getIsUserAttribute()
    {
        return in_array($this->from_email, auth()->user()?->person?->emails);
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(Credential::class);
    }

    public function fromPerson(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'from_person');
    }

    public function toPerson(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'to_person');
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
