<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Data\Matrix\MatrixSyncState;
use App\Models\Credential;
use App\Models\User;
use App\Repositories\MatrixClientSyncRepository;
use App\Services\Messaging\Matrix\MatrixEventHandlerRegistry;
use App\Services\Messaging\Matrix\MatrixEventSupport;
use Mockery;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class MatrixClientSyncRepositoryTest extends TestCase
{
    protected MatrixClientSyncRepository $repository;

    protected ?Credential $credential = null;

    protected ?User $user = null;

    protected function setUp(): void
    {
        parent::setUp();

        $logger = app(LoggerInterface::class);
        $registry = app(MatrixEventHandlerRegistry::class);
        $support = app(MatrixEventSupport::class);

        $this->repository = new MatrixClientSyncRepository($logger, $registry, $support);
        $this->user = User::factory()->make(['id' => 1]);
        $this->credential = Credential::factory()->make([
            'id' => 1,
            'user_id' => $this->user->id,
            'settings' => ['matrix_server' => 'https://matrix.test'],
            'access_token' => 'token',
        ]);
    }

    public function test_process_event_matrix_client_event(): void
    {
        $this->dispatch([
            'type' => 'io.element.matrix_client_information.DEVICE_ID',
            'content' => ['test' => 'test'],
        ]);

        $this->assertSame([
            'DEVICE_ID' => [
                'test' => 'test',
            ],
        ], $this->state()->devices());
        $this->assertSame([], $this->state()->keys());
        $this->assertNull($this->state()->defaultKey());
        $this->assertNull($this->state()->masterKey());
        $this->assertNull($this->state()->selfSignKey());
        $this->assertNull($this->state()->signingUser());
        $this->assertNull($this->state()->megolmBackup());
        $this->assertNull($this->state()->client());
    }

    public function test_process_event_local_notification_settings(): void
    {
        $this->dispatch([
            'type' => 'org.matrix.msc3890.local_notification_settings.DEVICE_ID',
            'content' => ['test' => 'test'],
        ]);

        $this->assertSame([
            'DEVICE_ID' => [
                'test' => 'test',
            ],
        ], $this->state()->devices());
        $this->assertSame([], $this->state()->keys());
    }

    public function test_process_event_secret_storage_key(): void
    {
        $this->dispatch([
            'type' => 'm.secret_storage.key.DEVICE_KEY',
            'content' => ['test' => 'test', 'object' => ['nested' => 'nested']],
        ]);

        $this->assertSame([
            'DEVICE_KEY' => [
                'test' => 'test',
                'object' => [
                    'nested' => 'nested',
                ],
            ],
        ], $this->state()->keys());
    }

    public function test_process_event_recent_emoji(): void
    {
        $this->dispatch([
            'type' => 'io.element.recent_emoji',
            'content' => ['recent_emoji' => [['emoji1'], ['emoji2']]],
        ]);

        $this->assertSame([
            'recent_emoji' => [
                'recent_emoji' => [
                    'emoji1',
                    'emoji2',
                ],
            ],
        ], $this->state()->devices());
    }

    public function test_process_event_secret_storage_default_key(): void
    {
        $this->dispatch([
            'type' => 'm.secret_storage.default_key',
            'content' => [
                'key' => 'A7I',
            ],
        ]);

        $this->assertSame('A7I', $this->state()->defaultKey());
    }

    public function test_process_event_cross_signing_master(): void
    {
        $payload = [
            'A7I' => [
                'iv' => base64_encode('asdf'),
                'ciphertext' => base64_encode('asdf'),
                'mac' => base64_encode('asdf'),
            ],
        ];

        $this->dispatch([
            'type' => 'm.cross_signing.master',
            'content' => [
                'encrypted' => $payload,
            ],
        ]);

        $this->assertSame($payload, $this->state()->masterKey());
    }

    public function test_process_event_cross_signing_self_signing(): void
    {
        $payload = [
            'A7I' => [
                'iv' => base64_encode('asdf'),
                'ciphertext' => base64_encode('asdf'),
                'mac' => base64_encode('asdf'),
            ],
        ];

        $this->dispatch([
            'type' => 'm.cross_signing.self_signing',
            'content' => [
                'encrypted' => $payload,
            ],
        ]);

        $this->assertSame($payload, $this->state()->selfSignKey());
    }

    public function test_process_event_cross_signing_user_signing(): void
    {
        $payload = [
            'A7I' => [
                'iv' => base64_encode('asdf'),
                'ciphertext' => base64_encode('asdf'),
                'mac' => base64_encode('asdf'),
            ],
        ];

        $this->dispatch([
            'type' => 'm.cross_signing.user_signing',
            'content' => [
                'encrypted' => $payload,
            ],
        ]);

        $this->assertSame($payload, $this->state()->signingUser());
    }

    public function test_process_event_megolm_backup(): void
    {
        $payload = [
            'A7I' => [
                'iv' => base64_encode('asdf'),
                'ciphertext' => base64_encode('asdf'),
                'mac' => base64_encode('asdf'),
            ],
        ];

        $this->dispatch([
            'type' => 'm.megolm_backup.v1',
            'content' => [
                'encrypted' => $payload,
            ],
        ]);

        $this->assertSame($payload, $this->state()->megolmBackup());
    }

    public function test_process_event_accepted_terms_is_ignored(): void
    {
        $this->dispatch([
            'type' => 'm.accepted_terms',
            'content' => ['test' => 'test'],
        ]);

        $this->assertSame([], $this->state()->devices());
        $this->assertSame([], $this->state()->keys());
    }

    public function test_process_event_web_settings(): void
    {
        $this->dispatch([
            'type' => 'im.vector.web.settings',
            'content' => ['test' => 'test'],
        ]);

        $this->assertSame(['test' => 'test'], $this->state()->client());
    }

    public function test_process_event_breadcrumbs(): void
    {
        $this->dispatch([
            'type' => 'im.vector.setting.breadcrumbs',
            'content' => ['test' => 'test'],
        ]);

        $this->assertSame(['test' => 'test'], $this->state()->breadcrumbs());
    }

    public function test_process_event_direct(): void
    {
        $payload = [
            '@discordbot:fake.tools' => [
                '!channel:fake.tools',
            ],
        ];

        $this->dispatch([
            'type' => 'm.direct',
            'content' => $payload,
        ]);

        $this->assertSame($payload, $this->state()->dms());
    }

    public function test_process_event_push_rules_is_ignored(): void
    {
        $this->dispatch([
            'type' => 'm.push_rules',
            'content' => ['test' => 'test'],
        ]);

        $this->assertSame([], $this->state()->devices());
        $this->assertSame([], $this->state()->keys());
    }

    public function test_it_logs_unhandled_events_with_payload(): void
    {
        $event = [
            'type' => 'm.unhandled',
            'content' => ['foo' => 'bar'],
        ];

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->expects('warning')
            ->once()
            ->withArgs(function (string $message, array $context) use ($event) {
                $this->assertSame('Unhandled Matrix event type', $message);
                $this->assertSame('m.unhandled', $context['type']);
                $this->assertNull($context['room']);
                $this->assertSame($event, $context['event']);

                return true;
            });

        $this->repository = new MatrixClientSyncRepository(
            $logger,
            app(MatrixEventHandlerRegistry::class),
            app(MatrixEventSupport::class),
        );

        $this->dispatch($event);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    protected function dispatch(array $event): void
    {
        $this->repository->processEvent($event, $this->credential, $this->user);
    }

    protected function state(): MatrixSyncState
    {
        /** @var MatrixSyncState $state */
        $state = $this->getProperty($this->repository, 'state');

        return $state;
    }
}
