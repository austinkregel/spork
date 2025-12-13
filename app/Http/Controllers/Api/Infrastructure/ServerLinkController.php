<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Infrastructure\ServerLinkRequest;
use App\Http\Resources\Infrastructure\ServerResource;
use App\Models\Domain;
use App\Models\Server;
use Illuminate\Http\JsonResponse;

class ServerLinkController extends Controller
{
    public function __invoke(ServerLinkRequest $request, Server $server): JsonResponse
    {
        $domains = Domain::query()
            ->whereIn('id', $request->validated('domain_ids'))
            ->get();

        $domains->each(function (Domain $domain) use ($server): void {
            $domain->server()->associate($server);
            $domain->save();
        });

        return response()->json([
            'server' => new ServerResource($server->load(['services', 'domains'])),
        ]);
    }
}







