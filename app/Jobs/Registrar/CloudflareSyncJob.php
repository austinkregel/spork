<?php

declare(strict_types=1);

namespace App\Jobs\Registrar;

use App\Events\Domains\DomainCreated;
use App\Models\Credential;
use App\Models\Domain;
use App\Models\Registrar;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CloudflareSyncJob extends AbstractSyncRegistrarResourceJob
{
    public function sync(): void
    {
        $page = 1;
        $registrar = Registrar::query()->firstOrCreate([
            'name' => 'Cloudflare',
        ]);
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
                    'registrar_id' => $registrar->id,
                ];

                if (empty($localDomain)) {
                    $localDomain = new Domain;
                    $localDomain->verification_key = 'stoned_'.Str::random(48);
                    $localDomain->credential_id = $this->credential->id;
                }

                foreach ($data as $key => $value) {
                    if ($localDomain->$key instanceof Carbon || $value instanceof Carbon) {
                        if ($value instanceof Carbon && ! $value->equalTo($localDomain->$key)) {
                            $localDomain->$key = $value;
                        } elseif ($localDomain->$key instanceof Carbon && ! $localDomain->$key->equalTo($value)) {
                            $localDomain->$key = $value;
                        }
                    } elseif ($localDomain->$key !== $value) {
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
