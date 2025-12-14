<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Http\Resources\Infrastructure\ActivityResource;
use App\Http\Resources\Infrastructure\DomainContactResource;
use App\Http\Resources\Infrastructure\DomainResource;
use App\Http\Resources\Infrastructure\DnsZoneResource;
use App\Http\Resources\Infrastructure\ServerResource;
use App\Models\Credential;
use App\Models\DnsZone;
use App\Models\DomainContact;
use App\Models\DomainRecord;
use App\Models\Server;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class InfrastructureOverviewService
{
    public function build(User $user, ?Request $request = null): array
    {
        $request ??= request();

        $servers = $user->servers()
            ->with(['services', 'domains', 'credential', 'providerCredential'])
            ->get();

        $domains = $user->domains()
            ->withoutGlobalScope('active')
            ->with(['server', 'dnsZone', 'contacts'])
            ->get();

        $dnsZones = DnsZone::query()
            ->where('user_id', $user->id)
            ->withCount(['records', 'domains'])
            ->get();

        $contacts = DomainContact::query()
            ->whereIn('domain_id', $domains->pluck('id'))
            ->get();

        $unhealthyServers = $servers->filter(fn (Server $server) => ! in_array($server->status, ['online', 'active'], true))->count();
        $expiringDomains = $domains->filter(fn ($domain) => $domain->expires_at && $domain->expires_at->diffInDays(now(), false) <= 30)->count();

        $providerStats = $domains
            ->groupBy(fn ($domain) => $domain->credential?->provider ?? 'unknown')
            ->map(fn ($items, $provider) => [
                'slug' => $provider,
                'name' => ucfirst($provider),
                'count' => $items->count(),
            ])
            ->values()
            ->all();

        $recentActivity = Activity::query()
            ->whereIn('subject_type', [
                Server::class,
                \App\Models\Domain::class,
                DomainRecord::class,
            ])
            ->latest()
            ->limit(25)
            ->get();

        return [
            'overview' => [
                'servers' => $servers->count(),
                'domains' => $domains->count(),
                'dns_zones' => $dnsZones->count(),
                'contacts' => $contacts->count(),
                'unhealthy_servers' => $unhealthyServers,
                'expiring_domains' => $expiringDomains,
            ],
            'spotlights' => [
                [
                    'id' => 'expiring',
                    'label' => 'Domains expiring soon',
                    'value' => $expiringDomains,
                    'description' => 'Renew within 30 days',
                ],
                [
                    'id' => 'unhealthy',
                    'label' => 'Servers needing attention',
                    'value' => $unhealthyServers,
                    'description' => 'Automation agents reported degraded status',
                ],
            ],
            'providerStats' => $providerStats,
            'servers' => ServerResource::collection($servers)->toArray($request),
            'domains' => DomainResource::collection($domains)->toArray($request),
            'dnsZones' => DnsZoneResource::collection($dnsZones)->toArray($request),
            'contacts' => DomainContactResource::collection($contacts)->toArray($request),
            'recentActivity' => ActivityResource::collection($recentActivity)->toArray($request),
            'providers' => $this->buildProviders($user),
        ];
    }

    protected function buildProviders(User $user): array
    {
        return $user->credentials()
            ->whereIn('type', [
                Credential::TYPE_SERVER,
                Credential::TYPE_DOMAIN,
                Credential::TYPE_REGISTRAR,
            ])
            ->get()
            ->map(function (Credential $credential) {
                $capabilities = $this->capabilitiesForCredential($credential);

                if (empty($capabilities)) {
                    return null;
                }

                return [
                    'id' => $credential->id,
                    'name' => $credential->name,
                    'provider' => $credential->service,
                    'type' => $credential->type,
                    'capabilities' => $capabilities,
                    'meta' => [
                        'account' => $credential->settings['account_name'] ?? $credential->settings['email'] ?? null,
                    ],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function capabilitiesForCredential(Credential $credential): array
    {
        $capabilities = [];

        if ($credential->type === Credential::TYPE_SERVER) {
            $capabilities[] = 'compute';
        }

        if (in_array($credential->type, [Credential::TYPE_DOMAIN, Credential::TYPE_REGISTRAR], true)) {
            $capabilities[] = 'dns';
        }

        return array_values(array_unique($capabilities));
    }
}

