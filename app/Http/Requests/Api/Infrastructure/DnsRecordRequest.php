<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Infrastructure;

use Illuminate\Foundation\Http\FormRequest;

class DnsRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'record_id' => ['nullable', 'integer', 'exists:domain_records,id'],
            'domain_id' => ['required', 'integer', 'exists:domains,id'],
            'type' => ['required', 'string', 'max:10'],
            'name' => ['required', 'string', 'max:255'],
            'value' => ['required', 'string', 'max:2048'],
            'ttl' => ['nullable', 'integer', 'min:60'],
            'proxied' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'min:0'],
        ];
    }
}







