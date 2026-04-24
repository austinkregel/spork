<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Infrastructure;

use Illuminate\Foundation\Http\FormRequest;

class DomainContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'string', 'max:50'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
