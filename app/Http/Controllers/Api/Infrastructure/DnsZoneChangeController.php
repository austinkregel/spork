<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Infrastructure\DnsZoneChangeRequest;
use App\Jobs\Infrastructure\ApplyDnsZoneChangesJob;
use App\Models\DnsZone;
use Illuminate\Http\JsonResponse;

class DnsZoneChangeController extends Controller
{
    public function __invoke(DnsZoneChangeRequest $request, DnsZone $dnsZone): JsonResponse
    {
        ApplyDnsZoneChangesJob::dispatch(
            dnsZoneId: $dnsZone->id,
            userId: $request->user()->id,
            changes: $request->validated('changes'),
        );

        return response()->json([
            'status' => 'queued',
        ]);
    }
}







