<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Messaging;

use App\Models\Credential;
use App\Services\Messaging\ImapCredentialService;
use Carbon\Carbon;
use Tests\TestCase;

class ImapProtonDateTest extends TestCase
{
    protected function makeService(): ImapCredentialService
    {
        $credential = new Credential([
            'settings' => [],
        ]);

        return new ImapCredentialService($credential);
    }

    public function test_parse_proton_date_handles_invalid_header_gracefully(): void
    {
        $service = $this->makeService();

        $method = new \ReflectionMethod(ImapCredentialService::class, 'parseProtonDate');
        $method->setAccessible(true);

        $result = $method->invoke($service, ['X-Pm-Date' => 'not-a-date']);

        $this->assertInstanceOf(Carbon::class, $result);
    }

    public function test_parse_proton_date_handles_missing_header_gracefully(): void
    {
        $service = $this->makeService();

        $method = new \ReflectionMethod(ImapCredentialService::class, 'parseProtonDate');
        $method->setAccessible(true);

        $result = $method->invoke($service, []);

        $this->assertInstanceOf(Carbon::class, $result);
    }
}
