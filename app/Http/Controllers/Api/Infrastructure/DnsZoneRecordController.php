<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Infrastructure\DnsRecordRequest;
use App\Models\DnsZone;
use App\Models\Domain;
use App\Models\DomainRecord;
use Illuminate\Http\JsonResponse;

class DnsZoneRecordController extends Controller
{
    public function store(DnsRecordRequest $request, DnsZone $dnsZone): JsonResponse
    {
        $data = $request->validated();

        /** @var Domain $domain */
        $domain = Domain::query()->findOrFail($data['domain_id']);

        $attributes = [
            'domain_id' => $domain->id,
            'dns_zone_id' => $dnsZone->id,
            'type' => $data['type'],
            'name' => $data['name'],
            'ttl' => $data['ttl'] ?? 300,
            'value' => $data['value'],
            'proxied_through_cloudflare' => $data['proxied'] ?? null,
            'priority' => $data['priority'] ?? null,
        ];

        if (! empty($data['record_id'])) {
            $record = DomainRecord::query()->where('id', $data['record_id'])->firstOrFail();
            abort_if($record->dns_zone_id && $record->dns_zone_id !== $dnsZone->id, 404);
            abort_if($record->domain_id !== $domain->id, 422, 'Record does not belong to the provided domain.');
            $record->update($attributes);
        } else {
            $record = DomainRecord::query()->create($attributes);
        }

        return response()->json([
            'record' => $record->fresh(),
        ], 201);
    }

    public function destroy(DnsZone $dnsZone, DomainRecord $record): JsonResponse
    {
        abort_if($record->dns_zone_id !== $dnsZone->id, 404);

        $record->delete();

        return response()->json([
            'status' => 'deleted',
        ]);
    }
}
