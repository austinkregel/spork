<?php

declare(strict_types=1);

namespace App\Http\Resources\Infrastructure;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\DomainContact */
class DomainContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'domain_id' => $this->domain_id,
            'role' => $this->role,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'meta' => $this->meta,
        ];
    }
}















