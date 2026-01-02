<?php

declare(strict_types=1);

namespace App\Http\Resources\Infrastructure;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\InfrastructureProvisionRequest */
class ProvisionRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'provider' => [
                'id' => $this->providerCredential?->id,
                'name' => $this->providerCredential?->name,
                'service' => $this->providerCredential?->service,
            ],
            'dns_provider' => $this->when($this->dnsCredential, [
                'id' => $this->dnsCredential?->id,
                'name' => $this->dnsCredential?->name,
                'service' => $this->dnsCredential?->service,
            ]),
            'server_id' => $this->server_id,
            'domain_id' => $this->domain_id,
            'result' => $this->result,
            'error' => $this->error,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}



















