<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\Initialize;
use App\Models\Credential;
use App\Models\User;
use App\Services\SshKeyGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InitializeTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_creates_ssh_key_if_not_exists(): void
    {
        $this->mock(SshKeyGeneratorService::class, function ($mock) {
            $mock->shouldReceive('generate')
                ->once()
                ->andReturn(['private_key', 'public_key']);
        });

        $this->artisan(Initialize::class)->assertExitCode(0);

        $this->assertDatabaseHas('credentials', ['type' => Credential::TYPE_SSH]);
    }

    public function test_handle_does_not_create_ssh_key_if_exists(): void
    {
        Credential::factory()->create(['type' => Credential::TYPE_SSH]);

        $this->artisan(Initialize::class)->expectsOutput('SSH key already exists')->assertExitCode(0);
    }

    public function test_handle_creates_user_if_not_exists(): void
    {
        $this->artisan(Initialize::class)->assertExitCode(0);

        $this->assertDatabaseHas('users', ['id' => 1]);
    }

    public function test_handle_creates_role_and_assigns_to_user(): void
    {
        $this->artisan(Initialize::class)->assertExitCode(0);

        $this->assertDatabaseHas('roles', ['name' => 'developer']);
        $this->assertDatabaseHas('model_has_roles', ['role_id' => Role::where('name', 'developer')->first()->id]);
    }

    public function test_handle_creates_permissions_for_crud_models(): void
    {
        $this->mock(Code::class, function ($mock) {
            $mock->shouldReceive('instancesOf')
                ->once()
                ->with(Crud::class)
                ->andReturn((object) ['files' => [new class {
                    public function getTable()
                    {
                        return 'example_table';
                    }
                }]]);
        });

        $this->artisan(Initialize::class)->assertExitCode(0);

        $permissions = [
            'create_example_table',
            'update_example_table',
            'delete_example_table',
            'view_any_example_table',
            'delete_any_example_table',
        ];

        foreach ($permissions as $permission) {
            $this->assertDatabaseHas('permissions', ['name' => $permission]);
        }
    }
}
