<?php

declare(strict_types=1);

namespace App\Jobs\Deployment\Steps;

use App\Jobs\Domains\VerifyDnsValue;
use App\Models\Credential;
use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use App\Services\Factories\DomainServiceFactory;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class SetupLoadBalancerDnsRecordJob implements ShouldQueue
{
    use DispatchesJobs, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public function __construct(
        public Server $server,
        public Domain $domain,
        public Project $project
    ) {
    }

    public function handle()
    {
        $dnsService = (new DomainServiceFactory)->make($this->project->credentialFor(Credential::CLOUDFLARE));
        $this->project->domains->each(function (Domain $domain) use ($dnsService) {
            $page = 1;
//            do {
//                $records = $dnsService->getDns($domain->cloudflare_id, 'A', 100, $page ++);
//
//                foreach ($records as $record) {
//                    if (
//                        $record['name'] === $domain->name &&
//                        $record['type'] === 'A' &&
//                        $record['content'] === $this->server->ip_address
//                    ) {
//                        return;
//                    }
//                }
//            } while ($records->hasMorePages());
//
//            $dnsService->createDnsRecord($domain->cloudflare_id, [
//                'type' => 'A',
//                'name' => '@',
//                'content' => $this->server->ip_address,
//                'ttl' => 3600,
//                // For forge, we can't have the dang SSL thing proxied
//                'proxied' => false,
//            ]);
            $dnsService->createDnsRecord($domain->cloudflare_id, [
                'type' => 'CNAME',
                'name' => '*',
                'content' => $domain->name,
                'ttl' => 3600,
                // For forge, we can't have the dang SSL thing proxied
                'proxied' => false,
            ]);
        });
//        dispatch_sync(new VerifyDnsValue($this->domain->name, 'A', $this->server->ip_address));
    }
}
