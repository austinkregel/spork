<?php

declare(strict_types=1);

namespace App\Http\Resources\Infrastructure;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\InfrastructureBulkOperation */
class BulkOperationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'operation' => $this->operation,
            'scope' => $this->scope,
            'filter' => $this->filter,
            'payload' => $this->payload,
            'status' => $this->status,
            'job_id' => $this->job_id,
            'result' => $this->result,
            'queued_at' => $this->queued_at?->toIso8601String(),
            'processed_at' => $this->processed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
