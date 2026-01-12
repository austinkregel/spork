<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Infrastructure;

use App\Models\Credential;
use Illuminate\Foundation\Http\FormRequest;

class MonitorIngestRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Prefer standard Authorization header, fall back to Authentication for backwards compatibility
        $authHeader = $this->header('Authorization') ?? $this->header('Authentication');

        if (! $authHeader) {
            \Log::warning('monitor.ingest.auth.missing_header', [
                'ip' => $this->ip(),
                'user_agent' => $this->userAgent(),
            ]);

            return false;
        }

        [$bearer, $token] = explode(' ', $authHeader, 2) + [null, null];

        if (strtolower((string) $bearer) !== 'bearer' || empty($token)) {
            \Log::warning('monitor.ingest.auth.invalid_format', [
                'ip' => $this->ip(),
                'user_agent' => $this->userAgent(),
                'header_present' => true,
            ]);

            return false;
        }

        $credential = Credential::query()
            ->where('api_key', $token)
            ->first();

        if (! $credential) {
            \Log::warning('monitor.ingest.auth.invalid_token', [
                'ip' => $this->ip(),
                'user_agent' => $this->userAgent(),
                'token_prefix' => substr($token, 0, 8).'...',
            ]);

            return false;
        }

        // Keep this flexible for now: allow monitor bridge credentials.
        if (! in_array($credential->type, [Credential::TYPE_DEVELOPMENT, Credential::TYPE_BACKUP_AGENT], true)) {
            \Log::warning('monitor.ingest.auth.invalid_credential_type', [
                'credential_id' => $credential->id,
                'credential_type' => $credential->type,
                'ip' => $this->ip(),
            ]);

            return false;
        }

        \Log::debug('monitor.ingest.auth.success', [
            'credential_id' => $credential->id,
            'credential_type' => $credential->type,
            'ip' => $this->ip(),
        ]);

        $this->merge([
            'credential' => $credential,
        ]);

        return true;
    }

    public function rules(): array
    {
        return [
            'event_type' => ['required', 'string', 'max:255'],
            'payload' => ['required', 'array'],
            'received_at' => ['nullable', 'date'],
        ];
    }

    public function credential(): Credential
    {
        /** @var Credential $credential */
        $credential = $this->input('credential');

        return $credential;
    }
}
