<?php

declare(strict_types=1);

namespace App\Jobs\Crm;

use App\Contracts\Services\Crm\MonicaClientContract;
use App\Models\Person;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncPersonToMonica implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Person $person,
    ) {
    }

    public function handle(MonicaClientContract $monica): void
    {
        $this->person->load('user');
        $this->person->refresh();

        $credential = $monica->findCredentialForUser($this->person->user);

        if (! $credential) {
            return;
        }

        $contactId = $monica->syncContact($credential, $this->person);

        if ($contactId === null) {
            return;
        }

        if ((string) $this->person->monica_contact_id !== (string) $contactId) {
            $this->person->monica_contact_id = (string) $contactId;
            $this->person->saveQuietly();
        }
    }
}

