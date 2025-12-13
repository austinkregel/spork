<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Infrastructure\DomainLinkRequest;
use App\Http\Resources\Infrastructure\DomainResource;
use App\Models\Domain;
use Illuminate\Http\JsonResponse;

class DomainLinkController extends Controller
{
    public function store(DomainLinkRequest $request, Domain $domain): JsonResponse
    {
        $data = $request->validated();

        $domain->server_id = $data['server_id'] ?? null;
        $domain->dns_zone_id = $data['dns_zone_id'] ?? null;
        $domain->save();

        return response()->json([
            'domain' => new DomainResource($domain->load(['server', 'dnsZone', 'contacts'])),
        ]);
    }

    public function destroy(Domain $domain): JsonResponse
    {
        $domain->server()->dissociate();
        $domain->dnsZone()->dissociate();
        $domain->save();

        return response()->json([
            'domain' => new DomainResource($domain->load(['server', 'dnsZone', 'contacts'])),
        ]);
    }
}







