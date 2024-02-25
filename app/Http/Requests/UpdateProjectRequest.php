<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->route('project')->team_id === $this->user()->current_team_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'string',
            'type' => 'string',
            'status' => 'string',
            'notes' => 'string',
            'start_date' => 'date',
            'checklist' => 'array',
            'checklist.*.name' => 'string',
            'checklist.*.checked' => 'boolean',
        ];
    }
}
