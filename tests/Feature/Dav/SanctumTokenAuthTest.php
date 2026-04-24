<?php

declare(strict_types=1);

namespace Tests\Feature\Dav;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SanctumTokenAuthTest extends DavTestCase
{
    use RefreshDatabase;

    public function test_propfind_without_credentials_is_rejected(): void
    {
        $response = $this->davRequest('PROPFIND', '/dav/');

        $response->assertStatus(401);
    }

    public function test_valid_dav_read_token_is_accepted(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user, ['dav:read']);

        $response = $this->davRequest('PROPFIND', '/dav/', $token, '', ['Depth' => '0']);

        $response->assertStatus(207);
    }

    public function test_token_without_dav_read_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user, ['read']);

        $response = $this->davRequest('PROPFIND', '/dav/', $token);

        $response->assertStatus(401);
    }

    public function test_revoked_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFor($user);

        $user->tokens()->delete();

        $response = $this->davRequest('PROPFIND', '/dav/', $token);

        $response->assertStatus(401);
    }
}
