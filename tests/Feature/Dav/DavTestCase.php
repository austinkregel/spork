<?php

declare(strict_types=1);

namespace Tests\Feature\Dav;

use App\Models\User;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

abstract class DavTestCase extends TestCase
{
    protected function davRequest(
        string $method,
        string $uri,
        ?string $token = null,
        string $body = '',
        array $headers = [],
    ): TestResponse {
        $headers['Accept'] = 'application/xml';

        if ($token !== null) {
            $headers['Authorization'] = 'Basic '.base64_encode('user:'.$token);
        }

        return $this->call($method, $uri, [], [], [], $this->serverArrayFor($headers, $method, $body), $body);
    }

    protected function tokenFor(User $user, array $abilities = ['dav:read', 'dav:write']): string
    {
        return $user->createToken('dav-test', $abilities)->plainTextToken;
    }

    private function serverArrayFor(array $headers, string $method, string $body): array
    {
        $server = [
            'CONTENT_TYPE' => $headers['Content-Type'] ?? 'application/xml',
            'CONTENT_LENGTH' => (string) strlen($body),
            'REQUEST_METHOD' => $method,
        ];

        foreach ($headers as $key => $value) {
            $serverKey = 'HTTP_'.strtoupper(str_replace('-', '_', $key));
            $server[$serverKey] = $value;
        }

        return $server;
    }
}
