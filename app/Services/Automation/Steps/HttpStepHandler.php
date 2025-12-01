<?php

declare(strict_types=1);

namespace App\Services\Automation\Steps;

use App\Models\Automation;
use App\Models\AutomationStep;
use App\Models\Credential;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class HttpStepHandler
{
    /**
     * @return array{output?:string,error?:string,data?:array}
     */
    public function execute(Automation $automation, AutomationStep $step): array
    {
        $config = $step->config ?? [];
        $method = strtoupper((string) ($config['method'] ?? 'GET'));
        $url = $config['url'] ?? null;

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return ['error' => 'HTTP step requires a valid URL'];
        }

        $timeout = max(1, (int) (($config['timeout_ms'] ?? 10000) / 1000));
        $request = Http::timeout($timeout);

        $headers = $this->normalizePairs($config['headers'] ?? []);
        if (! empty($headers)) {
            $request = $request->withHeaders($headers);
        }

        $auth = $config['auth'] ?? [];
        if (! empty($auth['bearer'])) {
            $request = $request->withToken($auth['bearer']);
        } elseif (! empty($auth['credential_id'])) {
            $token = $this->resolveCredentialToken((int) $auth['credential_id']);
            if ($token) {
                $request = $request->withToken($token);
            }
        }

        $options = [];

        $query = $this->normalizePairs($config['query'] ?? []);
        if (! empty($query)) {
            $options['query'] = $query;
        }

        $body = $config['body'] ?? null;
        if (! is_null($body)) {
            if (is_array($body)) {
                $options['json'] = $body;
            } elseif (is_string($body) && $body !== '') {
                $decoded = json_decode($body, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $options['json'] = $decoded;
                } else {
                    $options['body'] = $body;
                }
            }
        }

        try {
            $response = $request->send($method, $url, $options);
        } catch (\Throwable $e) {
            return ['error' => sprintf('HTTP request failed: %s', $e->getMessage())];
        }

        $payload = $response->json();
        $data = [];
        if (is_array($payload)) {
            $data = Arr::isAssoc($payload) ? [$payload] : $payload;
        } else {
            $data = [['body' => $response->body()]];
        }

        return [
            'output' => sprintf('%s %s => %d', $method, $url, $response->status()),
            'data' => $data,
        ];
    }

    protected function normalizePairs($value): array
    {
        if (is_array($value) && Arr::isAssoc($value)) {
            return $value;
        }

        if (! is_array($value)) {
            return [];
        }

        $assoc = [];
        foreach ($value as $pair) {
            if (! is_array($pair)) {
                continue;
            }
            $key = $pair['key'] ?? $pair['name'] ?? null;
            if (is_null($key) || $key === '') {
                continue;
            }
            $assoc[$key] = $pair['value'] ?? '';
        }

        return $assoc;
    }

    protected function resolveCredentialToken(int $credentialId): ?string
    {
        /** @var Credential|null $credential */
        $credential = Credential::query()->find($credentialId);

        if (! $credential) {
            return null;
        }

        return $credential->access_token
            ?? $credential->secret_key
            ?? $credential->api_key
            ?? null;
    }
}
