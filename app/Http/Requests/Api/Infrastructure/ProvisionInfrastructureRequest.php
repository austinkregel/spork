<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Infrastructure;

use Illuminate\Foundation\Http\FormRequest;

class ProvisionInfrastructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'provider_credential_id' => ['required', 'integer', 'exists:credentials,id'],
            'server' => ['required', 'array'],
            'server.name' => ['nullable', 'string', 'max:255'],
            'server.region' => ['required', 'string', 'max:255'],
            'server.size' => ['required', 'string', 'max:255'],
            'server.image' => ['nullable', 'string', 'max:255'],
            'server.tags' => ['nullable', 'array'],
            'server.tags.*' => ['string', 'max:255'],
            'server.ssh_key_ids' => ['nullable', 'array'],
            'server.ssh_key_ids.*' => ['integer'],
            'server.user_data' => ['nullable', 'string'],
            'domain_action' => ['required', 'in:none,link,create'],
            'existing_domain_id' => ['required_if:domain_action,link', 'integer', 'exists:domains,id'],
            'dns_provider_credential_id' => ['required_if:domain_action,create', 'integer', 'exists:credentials,id'],
            'new_domain' => ['nullable', 'array'],
            'new_domain.name' => ['required_if:domain_action,create', 'string', 'max:255'],
            'records' => ['nullable', 'array'],
            'records.*.type' => ['required_with:records', 'string', 'max:10'],
            'records.*.name' => ['required_with:records', 'string', 'max:255'],
            'records.*.value' => ['nullable', 'string', 'max:2048'],
            'records.*.proxied' => ['nullable', 'boolean'],
            'records.*.use_server_ip' => ['nullable', 'boolean'],
        ];
    }
}

