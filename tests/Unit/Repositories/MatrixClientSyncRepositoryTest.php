<?php

namespace Tests\Unit\Repositories;

use App\Repositories\MatrixClientSyncRepository;
use Tests\TestCase;

class MatrixClientSyncRepositoryTest extends TestCase
{
    protected MatrixClientSyncRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new MatrixClientSyncRepository();
    }

    public function testProcessEventMatrixClientEvent()
    {
        $this->repository->processEvent([
            'type' => 'io.element.matrix_client_information.DEVICE_ID',
            'content' => ['test' => 'test']
        ]);

        $this->assertSame([
            'DEVICE_ID' => [
                'test' => 'test'
            ]
        ], $this->getProperty($this->repository, 'devices'));
        $this->assertSame([], $this->getProperty($this->repository, 'keys'));
        $this->assertSame(null, $this->getProperty($this->repository, 'default_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'master_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'self_sign_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'signing_user'));
        $this->assertSame(null, $this->getProperty($this->repository, 'megolm_backup'));
        $this->assertSame(null, $this->getProperty($this->repository, 'client'));
        $this->assertSame(null, $this->getProperty($this->repository, 'notification_settings'));

    }

    public function testProcessEventLocalNotificationSettings()
    {
        $this->repository->processEvent([
            'type' => 'org.matrix.msc3890.local_notification_settings.DEVICE_ID',
            'content' => ['test' => 'test']
        ]);
        $this->assertSame([
            'DEVICE_ID' => [
                'test' => 'test'
            ]
        ], $this->getProperty($this->repository, 'devices'));
        $this->assertSame([], $this->getProperty($this->repository, 'keys'));
        $this->assertSame(null, $this->getProperty($this->repository, 'default_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'master_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'self_sign_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'signing_user'));
        $this->assertSame(null, $this->getProperty($this->repository, 'megolm_backup'));
        $this->assertSame(null, $this->getProperty($this->repository, 'client'));
        $this->assertSame(null, $this->getProperty($this->repository, 'notification_settings'));

    }

    public function testProcessEventSecretStorageKey()
    {
        $this->repository->processEvent([
            'type' => 'm.secret_storage.key.DEVICE_KEY',
            'content' => ['test' => 'test', 'object' => [ 'nested' => 'nested' ]]
        ]);
        $this->assertSame([
            'DEVICE_KEY' => [
                'test' => 'test',
                'object' => [
                    'nested' => 'nested'
                ]
            ]
        ], $this->getProperty($this->repository, 'keys'));
        $this->assertSame([], $this->getProperty($this->repository, 'devices'));
        $this->assertSame(null, $this->getProperty($this->repository, 'default_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'master_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'self_sign_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'signing_user'));
        $this->assertSame(null, $this->getProperty($this->repository, 'megolm_backup'));
        $this->assertSame(null, $this->getProperty($this->repository, 'client'));
        $this->assertSame(null, $this->getProperty($this->repository, 'notification_settings'));

    }

    public function testProcessEventRecentEmoji()
    {
        $this->repository->processEvent([
            'type' => 'io.element.recent_emoji',
            'content' => ['recent_emoji' => [['emoji1'], ['emoji2']]]
        ]);
        $this->assertSame([
            'recent_emoji' => [
                'recent_emoji' => [
                    'emoji1',
                    'emoji2',
                ]
            ]
        ], $this->getProperty($this->repository, 'devices'));
        $this->assertSame([], $this->getProperty($this->repository, 'keys'));
        $this->assertSame(null, $this->getProperty($this->repository, 'default_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'master_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'self_sign_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'signing_user'));
        $this->assertSame(null, $this->getProperty($this->repository, 'megolm_backup'));
        $this->assertSame(null, $this->getProperty($this->repository, 'client'));
        $this->assertSame(null, $this->getProperty($this->repository, 'notification_settings'));

    }

    public function testProcessEventSecretStorageDefaultKey()
    {
        $this->repository->processEvent([
            'type' => 'm.secret_storage.default_key',
            'content' =>  [
                'key' => 'A7I',
            ]
        ]);
        $this->assertSame([], $this->getProperty($this->repository, 'devices'));
        $this->assertSame([], $this->getProperty($this->repository, 'keys'));
        $this->assertSame('A7I', $this->getProperty($this->repository, 'default_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'master_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'self_sign_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'signing_user'));
        $this->assertSame(null, $this->getProperty($this->repository, 'megolm_backup'));
        $this->assertSame(null, $this->getProperty($this->repository, 'client'));
        $this->assertSame(null, $this->getProperty($this->repository, 'notification_settings'));
    }

    public function testProcessEventCrossSigningMaster()
    {
        $this->repository->processEvent([
            'type' => 'm.cross_signing.master',
            'content' => [
                'encrypted' =>  [
                    'A7I' =>  [
                        'iv' => base64_encode('asdf'),
                        'ciphertext' => base64_encode('asdf'),
                        'mac' => base64_encode('asdf'),
                    ]
                ]
            ]
        ]);
        $this->assertSame([], $this->getProperty($this->repository, 'devices'));
        $this->assertSame([], $this->getProperty($this->repository, 'keys'));
        $this->assertSame(null, $this->getProperty($this->repository, 'default_key'));
        $this->assertSame([
            'A7I' => [
                'iv' => base64_encode('asdf'),
                'ciphertext' => base64_encode('asdf'),
                'mac' => base64_encode('asdf'),
            ]
        ], json_decode(json_encode($this->getProperty($this->repository, 'master_key')), true));
        $this->assertSame(null, $this->getProperty($this->repository, 'self_sign_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'signing_user'));
        $this->assertSame(null, $this->getProperty($this->repository, 'megolm_backup'));
        $this->assertSame(null, $this->getProperty($this->repository, 'client'));
        $this->assertSame(null, $this->getProperty($this->repository, 'notification_settings'));
    }

    public function testProcessEventCrossSigningSelfSigning()
    {
        $this->repository->processEvent([
            'type' => 'm.cross_signing.self_signing',
            'content' => [
                'encrypted' =>  [
                    'A7I' =>  [
                        'iv' => base64_encode('asdf'),
                        'ciphertext' => base64_encode('asdf'),
                        'mac' => base64_encode('asdf'),
                    ]
                ]
            ]
        ]);
        $this->assertSame([], $this->getProperty($this->repository, 'devices'));
        $this->assertSame([], $this->getProperty($this->repository, 'keys'));
        $this->assertSame(null, $this->getProperty($this->repository, 'default_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'master_key'));
        $this->assertSame([
            'A7I' => [
                'iv' => base64_encode('asdf'),
                'ciphertext' => base64_encode('asdf'),
                'mac' => base64_encode('asdf'),
            ]
        ], $this->getProperty($this->repository, 'self_sign_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'signing_user'));
        $this->assertSame(null, $this->getProperty($this->repository, 'megolm_backup'));
        $this->assertSame(null, $this->getProperty($this->repository, 'client'));
        $this->assertSame(null, $this->getProperty($this->repository, 'notification_settings'));
    }

    public function testProcessEventCrossSigningUserSigning()
    {
        $this->repository->processEvent([
            'type' => 'm.cross_signing.user_signing',
            'content' => [
                'encrypted' =>  [
                    'A7I' =>  [
                        'iv' => base64_encode('asdf'),
                        'ciphertext' => base64_encode('asdf'),
                        'mac' => base64_encode('asdf'),
                    ]
                ]
            ]
        ]);
        $this->assertSame([], $this->getProperty($this->repository, 'devices'));
        $this->assertSame([], $this->getProperty($this->repository, 'keys'));
        $this->assertSame(null, $this->getProperty($this->repository, 'default_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'master_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'self_sign_key'));
        $this->assertSame([
            'A7I' => [
                'iv' => base64_encode('asdf'),
                'ciphertext' => base64_encode('asdf'),
                'mac' => base64_encode('asdf'),
            ]
        ], $this->getProperty($this->repository, 'signing_user'));
        $this->assertSame(null, $this->getProperty($this->repository, 'megolm_backup'));
        $this->assertSame(null, $this->getProperty($this->repository, 'client'));
        $this->assertSame(null, $this->getProperty($this->repository, 'notification_settings'));
    }

    public function testProcessEventMegolmBackup()
    {
        $this->repository->processEvent([
            'type' => 'm.megolm_backup.v1',
            'content' => [
                'encrypted' =>  [
                    'A7I' =>  [
                        'iv' => base64_encode('asdf'),
                        'ciphertext' => base64_encode('asdf'),
                        'mac' => base64_encode('asdf'),
                    ]
                ]
            ]
        ]);
        $this->assertSame([], $this->getProperty($this->repository, 'devices'));
        $this->assertSame([], $this->getProperty($this->repository, 'keys'));
        $this->assertSame(null, $this->getProperty($this->repository, 'default_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'master_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'self_sign_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'signing_user'));
        $this->assertSame([
            'A7I' => [
                'iv' => base64_encode('asdf'),
                'ciphertext' => base64_encode('asdf'),
                'mac' => base64_encode('asdf'),
            ]
        ], $this->getProperty($this->repository, 'megolm_backup'));
        $this->assertSame(null, $this->getProperty($this->repository, 'client'));
        $this->assertSame(null, $this->getProperty($this->repository, 'notification_settings'));
    }

    public function testProcessEventAcceptedTerms()
    {
        $this->repository->processEvent([
            'type' => 'm.accepted_terms',
            'content' => ['test' => 'test']
        ]);
        $this->assertSame([], $this->getProperty($this->repository, 'devices'));
        $this->assertSame([], $this->getProperty($this->repository, 'keys'));
        $this->assertSame(null, $this->getProperty($this->repository, 'default_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'master_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'self_sign_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'signing_user'));
        $this->assertSame(null, $this->getProperty($this->repository, 'megolm_backup'));
        $this->assertSame(null, $this->getProperty($this->repository, 'client'));
        $this->assertSame(null, $this->getProperty($this->repository, 'notification_settings'));
    }

    public function testProcessEventWebSettings()
    {
        $this->repository->processEvent([
            'type' => 'im.vector.web.settings',
            'content' => ['test' => 'test']
        ]);
        $this->assertSame([], $this->getProperty($this->repository, 'devices'));
        $this->assertSame([], $this->getProperty($this->repository, 'keys'));
        $this->assertSame(null, $this->getProperty($this->repository, 'default_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'master_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'self_sign_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'signing_user'));
        $this->assertSame(null, $this->getProperty($this->repository, 'megolm_backup'));
        $this->assertSame(['test' => 'test'], (array) $this->getProperty($this->repository, 'client'));
        $this->assertSame(null, $this->getProperty($this->repository, 'notification_settings'));
    }

    public function testProcessEventBreadcrumbs()
    {
        $this->testProcessEventWebSettings();
        $this->repository->processEvent([
            'type' => 'im.vector.setting.breadcrumbs',
            'content' => ['test' => 'test']
        ]);
        $this->assertSame([], $this->getProperty($this->repository, 'devices'));
        $this->assertSame([], $this->getProperty($this->repository, 'keys'));
        $this->assertSame(null, $this->getProperty($this->repository, 'default_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'master_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'self_sign_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'signing_user'));
        $this->assertSame(null, $this->getProperty($this->repository, 'megolm_backup'));
        $this->assertSame([
            'test' => 'test'
        ], json_decode(json_encode($this->getProperty($this->repository, 'breadcrumbs')), true));
        $this->assertSame(null, $this->getProperty($this->repository, 'notification_settings'));
    }

    public function testProcessEventDirect()
    {
        $this->testProcessEventWebSettings();
        $this->repository->processEvent([
            'type' => 'm.direct',
            'content' => [
                '@discordbot:fake.tools' => [
                    '!channel:fake.tools',
                ]
            ]
        ]);
        $this->assertSame([], $this->getProperty($this->repository, 'devices'));
        $this->assertSame([], $this->getProperty($this->repository, 'keys'));
        $this->assertSame(null, $this->getProperty($this->repository, 'default_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'master_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'self_sign_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'signing_user'));
        $this->assertSame(null, $this->getProperty($this->repository, 'megolm_backup'));
        $this->assertSame([
            '@discordbot:fake.tools' => [
                '!channel:fake.tools',
            ]
        ], json_decode(json_encode($this->getProperty($this->repository, 'dms')), true));
        $this->assertSame(null, $this->getProperty($this->repository, 'notification_settings'));
    }

    public function testProcessEventPushRules()
    {
        $this->repository->processEvent([
            'type' => 'm.push_rules',
            'content' => ['test' => 'test']
        ]);

        $this->assertSame([], $this->getProperty($this->repository, 'devices'));
        $this->assertSame([], $this->getProperty($this->repository, 'keys'));
        $this->assertSame(null, $this->getProperty($this->repository, 'default_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'master_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'self_sign_key'));
        $this->assertSame(null, $this->getProperty($this->repository, 'signing_user'));
        $this->assertSame(null, $this->getProperty($this->repository, 'megolm_backup'));
        $this->assertSame(null, $this->getProperty($this->repository, 'client'));
        $this->assertSame(null, $this->getProperty($this->repository, 'notification_settings'));
    }
}