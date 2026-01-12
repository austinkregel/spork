<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Infrastructure;

use App\Models\Credential;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterHostRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->hasHeader('Authorization')) {
            return false;
        }

        [$bearer, $token] = explode(' ', $this->header('Authorization'), 2) + [null, null];

        if (strtolower((string) $bearer) !== 'bearer' || empty($token)) {
            return false;
        }

        $credential = Credential::query()
            ->where('api_key', $token)
            ->first();

        if (! $credential) {
            return false;
        }

        if ($credential->type !== Credential::TYPE_SSH) {
            return false;
        }

        $this->merge([
            'credential' => $credential,
        ]);

        return true;
    }

    public function rules(): array
    {
        return [
            'machine_id' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'string', 'max:255'],
            'ip_address_v6' => ['nullable', 'string', 'max:255'],
            'internal_ip_address' => ['nullable', 'string', 'max:255'],
            'internal_ip_address_v6' => ['nullable', 'string', 'max:255'],
            'os' => ['nullable', 'string', 'max:255'],
            'booted_at' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:255'],
            'connection_type' => ['nullable', 'string', Rule::in(['agent', 'ssh', 'provider'])],
            'provider_server_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function credential(): Credential
    {
        /** @var Credential $credential */
        $credential = $this->input('credential');

        return $credential;
    }
}
