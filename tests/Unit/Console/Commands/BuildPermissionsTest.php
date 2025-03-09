<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\BuildPermissions;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BuildPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_creates_permissions_and_assigns_roles(): void
    {
        $this->artisan(BuildPermissions::class)->assertExitCode(0);

        $this->assertDatabaseHas('roles', ['name' => 'admin']);
        $this->assertDatabaseHas('roles', ['name' => 'developer']);

        $permissions = [
            'view_any_',
            'view_',
            'create_',
            'update_',
            'delete_',
            'delete_any_',
            'force_delete_',
            'force_delete_any_',
            'restore_',
            'restore_any_',
            'replicate_',
            'reorder_',
        ];

        foreach ($permissions as $permission) {
            $this->assertDatabaseHas('permissions', ['name' => $permission]);
        }

        $adminRole = Role::findByName('admin');
        $developerRole = Role::findByName('developer');

        $this->assertCount(count($permissions), $adminRole->permissions);
        $this->assertCount(count($permissions), $developerRole->permissions);

        $user = User::factory()->create();
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('developer'));
    }
}
