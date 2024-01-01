<?php

declare(strict_types=1);

namespace App\Actions\Spork;

use App\Jobs\FetchDomainsForCredential;
use App\Jobs\FetchRegistrarForCredential;
use App\Jobs\FetchServersForCredential;
use App\Jobs\Finance\SyncPlaidTransactionsJob;
use App\Jobs\Servers\LaravelForgeServersSyncJob;
use App\Models\Credential;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Request;

class SyncDataFromCredential extends CustomAction
{
    public function __construct($name = 'Sync Data From Credential', $slug = 'sync-data-from-credential')
    {
        parent::__construct($name, $slug, models: Credential::class);
    }

    public function __invoke(Dispatcher $dispatcher, Request $request)
    {
        $credentials = Credential::where('user_id', $request->user()->id)->whereIn('id', $request->get('items'))->get();

        foreach ($credentials as $credential) {
            $dispatcher->dispatch(match ($credential->type) {
                Credential::TYPE_REGISTRAR => new FetchRegistrarForCredential($credential),
                Credential::TYPE_DOMAIN => new FetchDomainsForCredential($credential),
                Credential::TYPE_SERVER => new FetchServersForCredential($credential),
                Credential::TYPE_DEVELOPMENT, 'forge' => new LaravelForgeServersSyncJob($credential),
                Credential::TYPE_FINANCE => new SyncPlaidTransactionsJob($credential, now()->subWeek(), now(), false),
            });
        }
    }
}
