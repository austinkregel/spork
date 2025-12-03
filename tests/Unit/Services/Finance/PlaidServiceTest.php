<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Finance;

use App\Services\Finance\PlaidHttpService;
use App\Services\Finance\PlaidService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Log\LogManager;
use Mockery;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class PlaidServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.plaid.env', 'sandbox');
        config()->set('services.plaid.client_id', 'client-id');
        config()->set('services.plaid.secret_key', 'secret-key');
        config()->set('services.plaid.version', '2020-09-14');
    }

    public function test_get_accounts_sends_json_payload_and_headers(): void
    {
        $history = [];
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'accounts' => [['account_id' => 'acc-1']],
            ], JSON_THROW_ON_ERROR)),
        ]);

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')->never();

        $service = $this->makePlaidService($mock, $history, $logger);

        $payload = $service->getAccounts('access-sandbox-123');

        $this->assertSame('acc-1', $payload['accounts'][0]['account_id']);
        $this->assertCount(1, $history);

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());

        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
        $this->assertSame('2020-09-14', $request->getHeaderLine('Plaid-Version'));
        $this->assertSame('client-id', $request->getHeaderLine('Plaid-Client-ID'));
        $this->assertSame('secret-key', $request->getHeaderLine('Plaid-Secret'));

        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('client-id', $body['client_id']);
        $this->assertSame('secret-key', $body['secret']);
        $this->assertSame('access-sandbox-123', $body['access_token']);
    }

    public function test_get_accounts_logs_plaid_error_details(): void
    {
        $history = [];
        $mock = new MockHandler([
            new Response(400, ['Plaid-Request-ID' => 'req-abc'], json_encode([
                'error_type' => 'INVALID_REQUEST',
                'error_code' => 'INVALID_FIELD',
                'error_message' => 'access_token is invalid',
                'display_message' => 'Please reconnect your bank.',
                'request_id' => 'req-abc',
            ], JSON_THROW_ON_ERROR)),
        ]);

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')
            ->once()
            ->with('Plaid request failed', Mockery::on(function (array $context): bool {
                $this->assertSame('/accounts/get', $context['endpoint']);
                $this->assertSame(400, $context['status_code']);
                $this->assertSame('INVALID_REQUEST', $context['error_type']);
                $this->assertSame('INVALID_FIELD', $context['error_code']);
                $this->assertSame('Please reconnect your bank.', $context['display_message'] ?? null);
                $this->assertArrayHasKey('token_hash', $context);

                return true;
            }));

        $service = $this->makePlaidService($mock, $history, $logger);

        $this->expectException(ClientException::class);
        $service->getAccounts('bad-token');
    }

    protected function makePlaidService(MockHandler $mock, array &$history, LoggerInterface $logger): PlaidService
    {
        $history = [];
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        $client = new Client([
            'handler' => $stack,
            'base_uri' => 'https://sandbox.plaid.com/',
        ]);

        $httpService = new PlaidHttpService($client);

        $logManager = Mockery::mock(LogManager::class);
        $logManager->shouldReceive('channel')
            ->once()
            ->with('plaid')
            ->andReturn($logger);

        return new PlaidService($httpService, $logManager);
    }
}

