<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;
use App\Http\Resources\Infrastructure\ActivityResource;
use App\Models\Domain;
use App\Models\DomainRecord;
use App\Models\Server;
use Illuminate\Http\JsonResponse;
use Spatie\Activitylog\Models\Activity;

class ActivityFeedController extends Controller
{
    public function servers(): JsonResponse
    {
        $activity = Activity::query()
            ->where('subject_type', Server::class)
            ->latest()
            ->limit(25)
            ->get();

        return response()->json([
            'activity' => ActivityResource::collection($activity),
        ]);
    }

    public function domains(): JsonResponse
    {
        $activity = Activity::query()
            ->whereIn('subject_type', [Domain::class, DomainRecord::class])
            ->latest()
            ->limit(25)
            ->get();

        return response()->json([
            'activity' => ActivityResource::collection($activity),
        ]);
    }
}
