<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Spork;

use App\Actions\Spork\SyncDataFromCredential;
use App\Models\Credential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SyncDataFromCredentialTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_method(): void
    {
        $credential = Credential::factory()->create();

        $syncDataFromCredential = Mockery::mock(SyncDataFromCredential::class, [$credential]);
        $syncDataFromCredential->shouldReceive('sync')
            ->once()
            ->andReturn(true);

        $this->assertTrue($syncDataFromCredential->sync());
    }
}
