<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Infrastructure;

use App\Models\Credential;
use Illuminate\Foundation\Http\FormRequest;

class MonitorIngestRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->hasHeader('Authentication')) {
            return false;
        }

        [$bearer, $token] = explode(' ', $this->header('Authentication'), 2) + [null, null];

        if (strtolower((string) $bearer) !== 'bearer' || empty($token)) {
            return false;
        }

        $credential = Credential::query()
            ->where('api_key', $token)
            ->first();

        if (! $credential) {
            return false;
        }

        // Keep this flexible for now: allow monitor bridge credentials.
        if (! in_array($credential->type, [Credential::TYPE_DEVELOPMENT, Credential::TYPE_BACKUP_AGENT], true)) {
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








