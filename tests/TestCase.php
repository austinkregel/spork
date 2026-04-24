<?php

declare(strict_types=1);

namespace Tests;

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    protected ?User $user = null;

    public function createApplication()
    {
        // PHPUnit points bootstrap caches at /tmp (see phpunit.xml). Stale files there can
        // reference removed packages (e.g. Nightwatch) and break the entire suite.
        foreach (['APP_SERVICES_CACHE', 'APP_PACKAGES_CACHE', 'APP_CONFIG_CACHE', 'APP_ROUTES_CACHE', 'APP_EVENTS_CACHE'] as $cache_env_key) {
            $path = $_ENV[$cache_env_key] ?? getenv($cache_env_key);
            if (is_string($path) && $path !== '' && is_file($path)) {
                @unlink($path);
            }
        }

        $app = require Application::inferBasePath().'/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    public function getProperty($object, string $property)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    public function createUserWithRole(string $role)
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function createUserWithPermissions(array $permissions)
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $permission]));
        }

        return $user;
    }

    public function actingAsUser(): static
    {
        if (! Role::firstWhere('name', 'developer')) {
            Role::create(['name' => 'developer']);
        }

        $this->actingAs($this->user = User::factory()->create([
            'email_verified_at' => now()->subHour(),
        ]));
        $this->user->assignRole('developer');

        return $this;
    }
}
