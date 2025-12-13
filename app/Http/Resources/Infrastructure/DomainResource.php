<?php

declare(strict_types=1);

namespace App\Http\Resources\Infrastructure;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Domain */
class DomainResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'provider' => $this->credential?->provider,
            'registrar' => $this->credential?->name,
            'expires_at' => $this->expires_at?->toDateString(),
            'auto_renew' => (bool) $this->getAttribute('auto_renew'),
            'server_id' => $this->server_id,
            'server' => $this->whenLoaded('server', fn () => [
                'id' => $this->server?->id,
                'name' => $this->server?->name,
                'status' => $this->server?->status,
            ]),
            'dns_zone_id' => $this->dns_zone_id,
            'dns_zone' => $this->whenLoaded('dnsZone', fn () => [
                'id' => $this->dnsZone?->id,
                'name' => $this->dnsZone?->name,
                'provider' => $this->dnsZone?->provider,
            ]),
            'contacts' => $this->whenLoaded('contacts', fn () => DomainContactResource::collection($this->contacts)),
        ];
    }
}

