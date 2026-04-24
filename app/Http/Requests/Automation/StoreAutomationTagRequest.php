<?php

declare(strict_types=1);

namespace App\Http\Requests\Automation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAutomationTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', Rule::in(['automatic', 'finance', 'server', ''])],
            'must_all_conditions_pass' => ['nullable', 'boolean'],
        ];
    }
}
