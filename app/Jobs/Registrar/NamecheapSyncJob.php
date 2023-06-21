<?php

namespace App\Jobs\Registrar;

use App\Events\Domains\DomainCreated;
use App\Models\Credential;
use App\Models\Domain;
use App\Models\Registrar;
use Illuminate\Support\Str;

class NamecheapSyncJob extends AbstractSyncRegistrarResourceJob
{
    // Sync credentials of a registrar, so we're ultimately getting a list of domains
    public function sync(): void
    {
        $page = 1;
        do {
            $domains = $this->service->getDomains(100, $page++);

            foreach ($domains as $domain) {
                $localDomain = Domain::where('credential_id', $this->credential->id)
                    ->where('domain_id', $domain['id'])
                    ->first();

                $data = [
                    'name' => $domain['domain'],
                    'domain_id' => $domain['id'],
                    'registered_at' => $domain['created_at'],
                    'expires_at' => $domain['expires_at'],
                    'verification_key' => 'reforged_'.Str::random(48),
                ];

                if (empty($localDomain)) {
                    $localDomain = new Domain;
                    $localDomain->credential_id = $this->credential->id;
                }

                foreach ($data as $key => $value) {
                    if ($localDomain->$key !== $value) {
                        // Only set the new value if its different
                        $localDomain->$key = $value;
                    }
                }

                if ($localDomain->isDirty() || ! $localDomain->exists()) {
                    $localDomain->save();
                    if ($localDomain->wasRecentlyCreated) {
                        event(new DomainCreated($localDomain, $this->credential, Credential::find(4)));
                    }
                }
            }
        } while ($domains->hasMorePages());
    }
}
