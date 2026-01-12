<?php

declare(strict_types=1);

namespace App\Http\Requests\Automation;

use App\Models\Condition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTagConditionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'parameter' => ['sometimes', 'string', 'max:255'],
            'comparator' => ['sometimes', 'string', Rule::in(Condition::ALL_COMPARATORS)],
            'value' => ['sometimes'],
        ];
    }
}
