<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Credential;
use App\Models\User;
use App\Rules\Credentials\UniqueCredentialForOwner;
use Illuminate\Contracts\Validation\ValidationRule;
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->user();

        $uniqueForOwner = new UniqueCredentialForOwner($user);

        // Historically, `type` is an organizational label while `service` is any supported provider.
        // Keep validation permissive across all known providers so existing behavior stays stable.
        $serviceOptions = array_values(array_unique(array_merge(
            Credential::ALL_FINANCE_PROVIDERS,
            Credential::ALL_DOMAIN_PROVIDERS,
            Credential::ALL_REGISTRAR_PROVIDERS,
            Credential::ALL_SERVER_PROVIDERS,
            Credential::ALL_CRM_PROVIDERS,
            Credential::ALL_SOURCE_PROVIDERS,
        )));

        return [
            'name' => 'required|string',
            'type' => [
                'required',
                'string',
                Rule::in([
                    Credential::TYPE_REGISTRAR,
                    Credential::TYPE_DOMAIN,
                    Credential::TYPE_SERVER,
                    Credential::TYPE_DEVELOPMENT,
                    Credential::TYPE_FINANCE,
                    Credential::TYPE_EMAIL,
                    Credential::TYPE_CRM,
                ]),
            ],
            'service' => [
                'required',
                'string',
                Rule::in($serviceOptions),
            ],
            'api_key' => ['nullable', $uniqueForOwner],
            'secret_key' => ['nullable'],
            'access_token' => ['nullable', 'string'],
            'refresh_token' => ['nullable', 'string'],
            'settings' => ['nullable', 'array'],
            'settings.*' => ['nullable'],
            'enabled_on' => 'nullable|date',
        ];
    }
}
