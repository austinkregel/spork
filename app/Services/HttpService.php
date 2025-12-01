<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\HttpServiceContract;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class HttpService implements HttpServiceContract
{
    protected Client $client;

    protected ?\Psr\Http\Message\ResponseInterface $response = null;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function client(): Client
    {
        return $this->client;
    }

    public function toArray()
    {
        return $this->response ? json_decode($this->response->getBody()->getContents(), true) : [];
    }

    public function get($path, $data = null)
    {
        return $this->request('get', $path, $data);
    }

    public function post($path, $data = null)
    {
        return $this->request('post', $path, $data);
    }

    public function patch($path, $data = null)
    {
        return $this->request('patch', $path, $data);
    }

    public function delete(string $path, $data = null)
    {
        return $this->request('delete', $path, $data);
    }

    public function put(string $path, $data = null)
    {
        return $this->request('put', $path, $data);
    }

    protected function request(string $method, string $path, $data = null)
    {
        $options = $data ?? [];

        $this->response = $this->client->request($method, $path, $options);

        return $this->response;
    }
}
