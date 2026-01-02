<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Infrastructure;

use Illuminate\Foundation\Http\FormRequest;

class DnsZoneChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'changes' => ['required', 'array', 'min:1'],
            'changes.*.type' => ['required', 'string'],
            'changes.*.payload' => ['required', 'array'],
        ];
    }
}



















