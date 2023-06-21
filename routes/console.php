<?php

use App\Jobs\Deployment\Steps\SetupCloudflareDns;
use App\Models\Credential;
use App\Services\LaravelForgeService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    dispatch_sync(new \App\Jobs\FetchCloudflareAnalytics());
})->purpose('Display an inspiring quote');

Artisan::command('verify:dns {domain} {expected}', function ($domain, $expected) {
    dispatch(new \App\Jobs\Domains\NameServerVerificationJob($domain, ['sid.ns.cloudflare.com', 'elle.ns.cloudflare.com']));
})->purpose('Display an inspiring quote');

Artisan::command('page-test', function () {
//    event(new \App\Events\Pages\PageUpdated(\App\Models\Page::first()));
    dispatch_sync(new \App\Jobs\Servers\LaravelForgeServersSyncJob(
        Credential::find(1),
    ));
});


Artisan::command('seed-servers', function () {
    $credential = Credential::find(2);
    dispatch_sync(new \App\Jobs\FetchRegistrarForCredential($credential));

    $credential = Credential::find(4);
    dispatch_sync(new \App\Jobs\CloudflareSyncAndPurgeJob($credential));

    $forgeCredentials = Credential::find(1);
    dispatch_sync(new \App\Jobs\Servers\LaravelForgeServersSyncJob($forgeCredentials));
});

Artisan::command('this-is-a-test', function () {
//    dispatch_sync(new \App\Jobs\Deployment\Strategies\SetupCloudflareDns(
//        \App\Models\Domain::find(1),
//         Credential::find(4)
//    ));
    $all = \App\Models\Domain::whereNull('cloudflare_id')->get();
    $credential = Credential::find(4);
    $registrar = Credential::find(2);
    foreach ($all as $domain) {
        dispatch_sync(new SetupCloudflareDns(
            $domain,
            $credential,
            $registrar
        ));
    }
//    dispatch_sync(new \App\Jobs\Deployment\Stratagies\SetupLoadBalancerJob(
//        \App\Models\Server::find(4),
//        \App\Models\Domain::find(1),
//        collect([]),
//        Credential::find(1)
//    ));
});
