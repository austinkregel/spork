<?php

declare(strict_types=1);

namespace App\Jobs\Infrastructure;

use App\Models\DnsZone;
use App\Models\Domain;
use App\Models\DomainContact;
use App\Models\InfrastructureBulkOperation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ProcessBulkInfrastructureOperationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $operationId) {}

    public function handle(): void
    {
        /** @var InfrastructureBulkOperation $operation */
        $operation = InfrastructureBulkOperation::query()->find($this->operationId);

        if (! $operation) {
            return;
        }

        $operation->update([
            'status' => 'processing',
            'queued_at' => $operation->queued_at ?? now(),
        ]);

        try {
            $result = match ($operation->operation) {
                'update-contact' => $this->handleUpdateContact($operation),
                'toggle-cloudflare' => $this->handleToggleCloudflare($operation),
                'update-nameservers' => $this->handleUpdateNameservers($operation),
                default => ['message' => 'Unknown operation.'],
            };

            $operation->update([
                'status' => 'completed',
                'result' => $result,
                'processed_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            $operation->update([
                'status' => 'failed',
                'result' => ['error' => $exception->getMessage()],
                'processed_at' => now(),
            ]);
        }
    }

    private function handleUpdateContact(InfrastructureBulkOperation $operation): array
    {
        $payload = $operation->payload;
        $domains = $this->filterDomains($operation->filter);

        $count = 0;

        $domains->each(function (Domain $domain) use ($payload, &$count): void {
            DomainContact::query()->updateOrCreate(
                ['domain_id' => $domain->id, 'role' => $payload['role'] ?? 'admin'],
                [
                    'name' => $payload['name'] ?? null,
                    'email' => $payload['email'] ?? null,
                    'phone' => $payload['phone'] ?? null,
                    'meta' => $payload['meta'] ?? null,
                ]
            );
            $count++;
        });

        return [
            'updated_contacts' => $count,
        ];
    }

    private function handleToggleCloudflare(InfrastructureBulkOperation $operation): array
    {
        $payload = $operation->payload;
        $feature = $payload['feature'] ?? 'proxy';
        $state = ($payload['state'] ?? 'enable') === 'enable';

        $zones = DnsZone::query()
            ->where('user_id', $operation->user_id)
            ->get();

        $zones->each(function (DnsZone $zone) use ($feature, $state): void {
            $settings = $zone->settings ?? [];
            $features = $settings['features'] ?? [];
            $features[$feature] = $state;
            $settings['features'] = $features;
            $zone->settings = $settings;
            $zone->save();
        });

        return [
            'feature' => $feature,
            'state' => $state ? 'enabled' : 'disabled',
            'zones_updated' => $zones->count(),
        ];
    }

    private function handleUpdateNameservers(InfrastructureBulkOperation $operation): array
    {
        $payload = $operation->payload;

        $zones = DnsZone::query()
            ->where('user_id', $operation->user_id)
            ->get();

        $zones->each(function (DnsZone $zone) use ($payload): void {
            $settings = $zone->settings ?? [];
            $settings['nameservers'] = [
                $payload['ns1'] ?? null,
                $payload['ns2'] ?? null,
            ];
            $zone->settings = $settings;
            $zone->save();
        });

        return [
            'zones_updated' => $zones->count(),
        ];
    }

    private function filterDomains(?string $filter): Collection
    {
        $query = Domain::query();

        if ($filter) {
            $terms = collect(explode(',', $filter))
                ->map(fn (string $term) => trim($term))
                ->filter();

            $query->where(function (Builder $builder) use ($terms): void {
                foreach ($terms as $term) {
                    $builder->orWhere('name', 'like', "%{$term}%");
                }
            });
        }

        return $query->get();
    }
}
