<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Infrastructure;

use Illuminate\Foundation\Http\FormRequest;

class ServerLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'domain_ids' => ['required', 'array', 'min:1'],
            'domain_ids.*' => ['integer', 'exists:domains,id'],
        ];
    }
}















