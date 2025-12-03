<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\HttpServiceContract;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;

class HttpService implements HttpServiceContract
{
    protected Client $client;

    protected ?\Psr\Http\Message\ResponseInterface $response = null;

    protected array $decodedResponse = [];

    public function __construct()
    {
        $this->client = new Client();
    }

    public function client(): Client
    {
        return $this->client;
    }

    public function toArray(): array
    {
        return $this->decodedResponse;
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

    protected function request(string $method, string $path, $data = null): Collection
    {
        [$options, $hasJsonPayload] = $this->formatRequestOptions($data);

        $options['headers'] = $this->buildHeaders($options['headers'] ?? [], $hasJsonPayload);

        $this->response = $this->client->request($method, $path, $options);
        $this->decodedResponse = $this->decodeResponse($this->response);

        return collect($this->decodedResponse);
    }

    /**
     * @return array{0: array<string,mixed>, 1: bool}
     */
    protected function formatRequestOptions($data): array
    {
        if ($data === null) {
            return [[], false];
        }

        if (is_array($data) && $this->containsDirectRequestPayload($data)) {
            return [$data, array_key_exists('json', $data)];
        }

        return [[
            'json' => $data,
        ], true];
    }

    protected function containsDirectRequestPayload(array $data): bool
    {
        $payloadKeys = ['json', 'form_params', 'multipart', 'body'];

        return count(array_intersect(array_keys($data), $payloadKeys)) > 0;
    }

    protected function buildHeaders(array $headers, bool $hasJsonPayload): array
    {
        $base = $this->defaultHeaders();

        if ($hasJsonPayload) {
            $base = array_merge([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ], $base);
        }

        return array_merge($base, $headers);
    }

    protected function defaultHeaders(): array
    {
        return [];
    }

    protected function decodeResponse(\Psr\Http\Message\ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $response->getBody()->rewind();

        if ($body === '') {
            return [];
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }
}
