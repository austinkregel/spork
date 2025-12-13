<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Infrastructure;

use Illuminate\Foundation\Http\FormRequest;

class DomainLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'server_id' => ['nullable', 'integer', 'exists:servers,id'],
            'dns_zone_id' => ['nullable', 'integer', 'exists:dns_zones,id'],
        ];
    }
}







