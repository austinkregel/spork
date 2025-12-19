<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Infrastructure;

use Illuminate\Foundation\Http\FormRequest;

class BulkOperationRequest extends FormRequest
{
    private const OPERATIONS = [
        'update-contact',
        'toggle-cloudflare',
        'update-nameservers',
    ];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'operation' => ['required', 'string', 'in:'.implode(',', self::OPERATIONS)],
            'scope' => ['required', 'string', 'in:domains,servers'],
            'filter' => ['nullable', 'string', 'max:255'],
            'payload' => ['required', 'array'],
        ];
    }
}















