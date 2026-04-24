<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Infrastructure\BulkOperationRequest;
use App\Http\Resources\Infrastructure\BulkOperationResource;
use App\Jobs\Infrastructure\ProcessBulkInfrastructureOperationJob;
use App\Models\InfrastructureBulkOperation;
use Illuminate\Http\JsonResponse;

class BulkOperationController extends Controller
{
    public function store(BulkOperationRequest $request): JsonResponse
    {
        $operation = InfrastructureBulkOperation::query()->create([
            'user_id' => $request->user()->id,
            'operation' => $request->validated('operation'),
            'scope' => $request->validated('scope'),
            'filter' => $request->validated('filter'),
            'payload' => $request->validated('payload'),
            'queued_at' => now(),
        ]);

        ProcessBulkInfrastructureOperationJob::dispatch($operation->id);

        return response()->json(new BulkOperationResource($operation), 202);
    }

    public function show(InfrastructureBulkOperation $operation): JsonResponse
    {
        $this->authorizeOperation($operation);

        return response()->json(new BulkOperationResource($operation));
    }

    private function authorizeOperation(InfrastructureBulkOperation $operation): void
    {
        abort_if(auth()->id() !== $operation->user_id, 403);
    }
}
