<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;
use App\Services\Infrastructure\InfrastructureOverviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InfrastructureOverviewController extends Controller
{
    public function __construct(
        private readonly InfrastructureOverviewService $overviewService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        return response()->json(
            $this->overviewService->build($request->user(), $request)
        );
    }
}
