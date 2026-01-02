<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;
use App\Models\Credential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MonitorBridgeTokenController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user, 401);

        $credential = $user->credentials()
            ->where('type', Credential::TYPE_BACKUP_AGENT)
            ->where('service', 'monitor-bridge')
            ->first();

        if (! $credential) {
            $credential = $user->credentials()->create([
                'type' => Credential::TYPE_BACKUP_AGENT,
                'service' => 'monitor-bridge',
                'name' => 'Monitor Bridge',
                'api_key' => Str::random(64),
                'settings' => [
                    'kind' => 'monitor-bridge',
                ],
                'enabled_on' => now(),
            ]);
        }

        // Never return the entire credential; it hides api_key anyway.
        return response()->json([
            'credential_id' => $credential->id,
            'token' => $credential->api_key,
        ]);
    }
}




