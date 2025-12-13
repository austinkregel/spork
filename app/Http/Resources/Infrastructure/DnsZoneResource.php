<?php

declare(strict_types=1);

namespace App\Http\Resources\Infrastructure;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\DnsZone */
class DnsZoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'provider' => $this->provider,
            'account' => $this->account,
            'external_id' => $this->external_id,
            'records_count' => $this->whenCounted('records'),
            'domains_count' => $this->whenCounted('domains'),
            'settings' => $this->settings,
        ];
    }
}







