<?php

namespace App\Jobs;

use App\Models\Credential;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class FetchCloudflareAnalytics implements ShouldQueue
{
    use Queueable, InteractsWithQueue;

    public function handle()
    {
        $service = new \App\Services\Domain\CloudflareDomainService(Credential::find(4));
        $domains = \App\Models\Domain::whereNotNull('cloudflare_id')->get();

        foreach ($domains as $domain) {
            $service->getAnalytics($domain, now()->subDay()->startOfDay(), now()->subDay()->endOfDay());
        }
    }
}
