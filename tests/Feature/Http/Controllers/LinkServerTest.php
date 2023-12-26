<?php

declare(strict_types=1);

namespace Feature\Http\Controllers;

use App\Models\Credential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkServerTest extends TestCase
{
    use RefreshDatabase;

    public function testFailsToCreateDevice()
    {
        $request = $this->postJson(route('create-device'), []);

        $request->assertStatus(422);
    }

    public function testCanCreateDevice()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->codes()->create([
            'short_code' => '384',
            'is_enabled' => true,
            'long_url' => 'http://fake.tools',
            'status' => 301,
        ]);

        $request = $this->actingAs($user)->postJson(route('create-device'), [
            'name' => 'aqua-depths',
            'threads' => 1,
            'memory' => 512,
            'short_code' => '384',
        ]);

        $request->assertStatus(201);
        $request->assertJson([
            'ssh_key_public' => Credential::first()->getPublicKey(),
        ]);
    }

    public function testCreateShortCodeUrlForDeviceRegistration()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $request = $this->actingAs($user)->getJson(route('setup-device'));

        $request->assertStatus(200);
        $request->assertJsonStructure([
            'route',
        ]);
    }

    public function testWeProvideRedirectLinkWithAuth()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->assertEmpty($user->codes()->get());

        $request = $this->actingAs($user)->getJson(route('setup-device'));

        $codes = $user->codes()->get();

        $this->assertNotEmpty($codes);

        $request->assertStatus(200);
        $route = $request->json('route');

        $this->assertNotEmpty($route);
        $otherRequest = $this->get($route);

        $otherRequest->assertStatus(301);

        $routeUsedToLinkDevice = route('register-device').'?short_code='.$codes->first()->short_code;

        $otherRequest->assertRedirect($routeUsedToLinkDevice);

        $attemptToCreateDevice = $this->postJson($routeUsedToLinkDevice, [
            'name' => 'twelve-foot-ninja',
            'memory' => 512,
            'threads' => 1,
        ]);

        $attemptToCreateDevice->assertStatus(201);

        $this->assertDatabaseHas('servers', [
            'name' => 'twelve-foot-ninja',
        ]);
    }
}
