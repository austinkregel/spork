<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Credential;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCredentialRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var User $user */
        $user = $this->user();

        return $user->can('create_credentials');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'type' => [
                'required',
                'string',
                Rule::in([
                    Credential::TYPE_DOMAIN,
                    Credential::TYPE_REGISTRAR,

                ]),
            ],
            'service' => [
                'required',
                'string',
                Rule::in([
                    Credential::CLOUDFLARE,
                    Credential::DIGITAL_OCEAN,
                    Credential::NAMECHEAP,
                    Credential::GITHUB_SOURCE
                ]),
            ],
            'api_key' => 'nullable',
            'secret_key' => 'nullable',
            'access_token' => 'nullable|string',
            'refresh_token' => 'nullable|string',
            'settings' => [],
            'settings.*' => 'nullable|string',
            'enabled_on' => 'nullable|date',
        ];
    }
}
