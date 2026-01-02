<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'settings' => [
                'nullable',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null) {
                        return;
                    }

                    if (is_array($value)) {
                        return;
                    }

                    if (is_string($value) && trim($value) === '') {
                        return;
                    }

                    if (is_string($value)) {
                        json_decode($value, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $fail('The '.$attribute.' must be a valid JSON string or an object.');
                        }

                        return;
                    }

                    $fail('The '.$attribute.' must be a valid JSON string or an object.');
                },
            ],
            'user_id' => ['required', 'exists:users,id'],

            'attachments' => ['sometimes', 'array'],
            'attachments.*.resource_type' => ['required', 'string'],
            'attachments.*.resource_id' => ['required', 'integer'],
        ];
    }
}
