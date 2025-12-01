<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Services\HttpService;
use GuzzleHttp\Client;

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

    /**
     * @var array
     */
    protected $authBits = [];

    public function __construct()
    {
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
        $this->authBits = $data;

        return $this;
    }

    protected function request(string $method, string $path, $data = null)
    {
        return parent::request($method, $path, array_merge((array) $data, $this->authBits));
    }

    protected function swapClientForEnvironment(string $env): void
    {
        $this->client = new Client([
            'base_uri' => sprintf($this->baseUrl, $env),
        ]);
    }
}
