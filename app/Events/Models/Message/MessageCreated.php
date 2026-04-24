<?php

declare(strict_types=1);

namespace App\Events\Models\Message;

use App\Events\AbstractLogicalEvent;
use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Str;

class MessageCreated extends AbstractLogicalEvent implements ShouldBroadcast
{
    public function __construct(
        public Message $model,
    ) {}

    public function broadcastOn(): array
    {
        $this->model->load('credential');

        return [
            new PrivateChannel('App.Models.User.'.$this->model->credential->user_id),
        ];
    }

    public function broadcastWith(): array
    {
        $this->model->loadMissing([
            'fromPerson',
            'replyTo.fromPerson',
            'thread',
        ]);

        return [
            'id' => $this->model->id,
            'thread_id' => $this->model->thread_id,
            'thread_name' => $this->model->thread?->name,
            'event_id' => $this->model->event_id,
            'originated_at' => $this->model->originated_at,
            'is_decrypted' => $this->model->is_decrypted,
            'from_person' => $this->model->fromPerson ? [
                'id' => $this->model->fromPerson->id,
                'name' => $this->model->fromPerson->name,
                'photo_url' => $this->model->fromPerson->photo_url,
            ] : $this->model->from_person,
            'preview' => $this->previewFor($this->model),
            'reply_to' => $this->model->replyTo ? [
                'id' => $this->model->replyTo->id,
                'event_id' => $this->model->replyTo->event_id,
                'from_person' => $this->model->replyTo->fromPerson ? [
                    'id' => $this->model->replyTo->fromPerson->id,
                    'name' => $this->model->replyTo->fromPerson->name,
                    'photo_url' => $this->model->replyTo->fromPerson->photo_url,
                ] : $this->model->replyTo->from_person,
                'preview' => $this->previewFor($this->model->replyTo),
            ] : null,
        ];
    }

    protected function previewFor(Message $message): ?string
    {
        $candidate = null;

        if ($message->is_decrypted && is_string($message->message) && $message->message !== '') {
            $candidate = $message->message;
        } elseif (is_string($message->subject) && $message->subject !== '') {
            $candidate = $message->subject;
        }

        if (! $candidate) {
            return null;
        }

        $candidate = preg_replace('/\s+/', ' ', trim(strip_tags($candidate))) ?: null;

        if (! $candidate) {
            return null;
        }

        return Str::limit($candidate, 140);
    }
}
