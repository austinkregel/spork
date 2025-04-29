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
