<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Credential;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class FetchCloudflareAnalytics implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public function handle()
    {
        $services = Credential::where('service', 'cloudflare')->get();

        foreach ($services as $service) {
            $service = new \App\Services\Domain\CloudflareDomainService($service);
            $domains = \App\Models\Domain::whereNotNull('cloudflare_id')->get();

            foreach ($domains as $domain) {
                $service->getAnalytics($domain, now()->subDay()->startOfDay(), now()->endOfDay());
            }
        }
    }
}
