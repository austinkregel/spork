<?php

declare(strict_types=1);

namespace App\Http\Resources\Infrastructure;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Server */
class ServerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'ip_address' => $this->ip_address,
            'provider' => $this->credential?->provider,
            'provider_label' => $this->credential?->name,
            'services' => $this->whenLoaded('services', fn () => $this->services->map(fn ($service) => [
                'id' => $service->id,
                'service' => $service->service,
                'status' => $service->status,
                'version' => $service->version,
            ])),
            'domains' => $this->whenLoaded('domains', fn () => $this->domains->map(fn ($domain) => [
                'id' => $domain->id,
                'name' => $domain->name,
            ])),
            'last_ping_at' => $this->last_ping_at?->toIso8601String(),
            'booted_at' => $this->booted_at?->toIso8601String(),
            'cost_per_hour' => $this->cost_per_hour,
        ];
    }
}







