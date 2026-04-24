<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Services\HttpService;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;

class PlaidHttpService extends HttpService
{
    /**
     * @var string
     */
    protected $env = 'sandbox';

    /**
     * @var string
     */
    protected $baseUrl = 'https://%s.plaid.com/';

    protected array $authBits = [];

    protected array $headerAuthBits = [];

    protected string $plaidVersion;

    protected ?Client $clientOverride = null;

    public function __construct(?Client $client = null)
    {
        $this->plaidVersion = (string) config('services.plaid.version', '2020-09-14');
        $this->clientOverride = $client;
        $this->swapClientForEnvironment($this->env);
    }

    public function sandbox(): self
    {
        return $this->use('sandbox');
    }

    public function development(): self
    {
        return $this->use('development');
    }

    public function production(): self
    {
        return $this->use('production');
    }

    protected function use(string $env): self
    {
        $this->env = $env;
        $this->swapClientForEnvironment($env);

        return $this;
    }

    public function auth($data): HttpService
    {
        $this->authBits = (array) $data;
        $this->headerAuthBits = array_filter([
            'Plaid-Client-ID' => $this->authBits['client_id'] ?? null,
            'Plaid-Secret' => $this->authBits['secret'] ?? null,
        ]);

        return $this;
    }

    protected function request(string $method, string $path, $data = null): Collection
    {
        return parent::request($method, $path, $this->mergePayloadWithAuth($data));
    }

    protected function mergePayloadWithAuth($data)
    {
        if ($data === null) {
            return $this->authBits;
        }

        if (! is_array($data)) {
            return $data;
        }

        if (array_key_exists('json', $data) && is_array($data['json'])) {
            $data['json'] = array_merge($data['json'], $this->authBits);

            return $data;
        }

        return array_merge($data, $this->authBits);
    }

    protected function defaultHeaders(): array
    {
        return array_filter(array_merge([
            'Plaid-Version' => $this->plaidVersion,
        ], $this->headerAuthBits));
    }

    protected function swapClientForEnvironment(string $env): void
    {
        $this->client = $this->clientOverride ?? new Client([
            'base_uri' => sprintf($this->baseUrl, $env),
        ]);
    }
}
