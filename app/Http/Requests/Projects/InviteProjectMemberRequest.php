<?php

declare(strict_types=1);

namespace App\Http\Requests\Projects;

use App\Models\ProjectMembershipRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteProjectMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project !== null
            && $this->user() !== null
            && $this->user()->can('invite', $project);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'string', Rule::in(ProjectMembershipRole::values())],
        ];
    }
}
