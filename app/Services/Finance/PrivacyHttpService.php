<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Services\HttpService;
use GuzzleHttp\Client;

class PrivacyHttpService extends HttpService
{
    protected string $baseUrl = 'https://api.privacy.com/';

    protected array $headerAuthBits = [];

    protected ?Client $clientOverride = null;

    public function __construct(?Client $client = null)
    {
        $this->clientOverride = $client;
        $this->client = $this->clientOverride ?? new Client([
            'base_uri' => $this->baseUrl,
        ]);
    }

    public function auth(string $apiKey): self
    {
        $this->headerAuthBits = [
            'Authorization' => sprintf('api-key %s', $apiKey),
        ];

        return $this;
    }

    protected function defaultHeaders(): array
    {
        return array_filter(array_merge([
            'Accept' => 'application/json',
        ], $this->headerAuthBits));
    }
}
