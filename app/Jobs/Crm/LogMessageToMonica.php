<?php

declare(strict_types=1);

namespace App\Jobs\Crm;

use App\Contracts\Services\Crm\MonicaClientContract;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogMessageToMonica implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Message $message,
    ) {
    }

    public function handle(MonicaClientContract $monica): void
    {
        $message = Message::query()
            ->with(['fromPerson', 'toPerson', 'credential.user'])
            ->find($this->message->getKey());

        if (! $message) {
            return;
        }

        $user = $message->credential?->user;
        $author = $message->fromPerson;
        $recipient = $message->toPerson;

        if (! $user || ! $author || ! $recipient) {
            return;
        }

        if ((int) $author->user_id !== (int) $user->id) {
            return;
        }

        $credential = $monica->findCredentialForUser($user);

        if (! $credential) {
            return;
        }

        $contactId = $recipient->monica_contact_id ?: $monica->syncContact($credential, $recipient);

        if (! $contactId) {
            return;
        }

        if ((string) $recipient->monica_contact_id !== (string) $contactId) {
            $recipient->monica_contact_id = (string) $contactId;
            $recipient->saveQuietly();
        }

        $monica->createConversation($credential, $contactId, $message, $recipient);
    }
}









