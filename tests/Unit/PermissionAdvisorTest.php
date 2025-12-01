<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Development\DescribeTable\PermissionAdvisor;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\Authenticatable;
use Mockery;
use Tests\Fixtures\Models\ExampleModel;
use Tests\TestCase;

class PermissionAdvisorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testAdvisorFallsBackToDeveloperRole(): void
    {
        $user = new FakeDeveloperUser();

        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('user')->andReturn($user);

        $auth = Mockery::mock(AuthFactory::class);
        $auth->shouldReceive('guard')->andReturn($guard);

        $advisor = new PermissionAdvisor($auth);
        $permissions = $advisor->forModel(new ExampleModel());

        $this->assertTrue($permissions['create']);
        $this->assertTrue($permissions['update']);
        $this->assertTrue($permissions['delete']);
        $this->assertTrue($permissions['delete_any']);
    }
}

class FakeDeveloperUser implements Authenticatable
{
    use AuthenticatableTrait;

    public function can($ability, $arguments = [])
    {
        return false;
    }

    public function hasRole(string $role): bool
    {
        return $role === 'developer';
    }
}

