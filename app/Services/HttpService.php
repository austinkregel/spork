<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class HttpService
{
    protected static $instance;

    public $data;

    public $url;

    public $path;

    public $client;

    public $response;

    public function __construct($url, $data = [])
    {
        $this->url = $url;
        $this->new($url, $data);
    }

    public static function __callStatic($method, $arguments)
    {
        if (empty(self::$instance)) {
            self::$instance = new static;
        }

        return call_user_func_array([self::$instance, $method], $arguments);
    }

    public function __call($method, $arguments)
    {
        return call_user_func_array([self::$instance, $method], $arguments);
    }

    public function toArray()
    {
        return $this->response->toArray();
    }

    protected function new($path, $data = [])
    {
        $this->path = $path;
        $this->client = new Client(array_merge(['base_uri' => $this->url], $data));

        return $this;
    }

    protected function request($action, $data = [])
    {
        if (! in_array(strtolower($action), ['get', 'post', 'put', 'delete', 'patch'])) {
            throw new \Exception('Your desired action is not supported');
        }

        if (empty($data)) {
            $body = [
                'body' => '{}',
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accepts' => 'application/json',
                ],
            ];
        } else {
            $body = [
                'json' => $data,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accepts' => 'application/json',
                ],
            ];
        }

        try {
            $response = $this->client->request($action, $this->path, $body);

            $json = $response->getBody()->getContents();
        } catch (ClientException $exception) {
            throw new \Exception($exception->getResponse()->getBody()->getContents());
        }

        return $this->response = collect(json_decode($json));
    }

    public function get($path, $data = null)
    {
        $this->path = $path;

        return $this->request('get', $data);
    }

    public function post($path, $data = null)
    {
        $this->path = $path;

        return $this->request('post', $data);
    }

    public function patch($path, $data = null)
    {
        $this->path = $path;

        return $this->request('patch', $data);
    }

    public function delete(string $path, $data = null)
    {
        $this->path = $path;

        return $this->request('delete', $data);
    }

    public function put(string $path, $data = null)
    {
        $this->path = $path;

        return $this->request('put', $data);
    }
}
