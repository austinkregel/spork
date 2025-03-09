<?php

declare(strict_types=1);

namespace Tests\Unit\Contracts;

use App\Contracts\PolicyContract;
use App\Models\Crud;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class PolicyContractTest extends TestCase
{
    public function test_view_any_method()
    {
        $user = $this->createMock(User::class);
        $policy = $this->getMockForAbstractClass(PolicyContract::class);

        $this->assertTrue($policy->viewAny($user));
    }

    public function test_view_method()
    {
        $user = $this->createMock(User::class);
        $model = $this->createMock(Crud::class);
        $policy = $this->getMockForAbstractClass(PolicyContract::class);

        $this->assertTrue($policy->view($user, $model));
    }

    public function test_create_method()
    {
        $user = $this->createMock(User::class);
        $policy = $this->getMockForAbstractClass(PolicyContract::class);

        $this->assertTrue($policy->create($user));
    }

    public function test_update_method()
    {
        $user = $this->createMock(User::class);
        $model = $this->createMock(Crud::class);
        $policy = $this->getMockForAbstractClass(PolicyContract::class);

        $this->assertTrue($policy->update($user, $model));
    }

    public function test_delete_method()
    {
        $user = $this->createMock(User::class);
        $model = $this->createMock(Crud::class);
        $policy = $this->getMockForAbstractClass(PolicyContract::class);

        $this->assertTrue($policy->delete($user, $model));
    }

    public function test_delete_any_method()
    {
        $user = $this->createMock(User::class);
        $policy = $this->getMockForAbstractClass(PolicyContract::class);

        $this->assertTrue($policy->deleteAny($user));
    }

    public function test_force_delete_method()
    {
        $user = $this->createMock(User::class);
        $model = $this->createMock(Crud::class);
        $policy = $this->getMockForAbstractClass(PolicyContract::class);

        $this->assertTrue($policy->forceDelete($user, $model));
    }

    public function test_force_delete_any_method()
    {
        $user = $this->createMock(User::class);
        $policy = $this->getMockForAbstractClass(PolicyContract::class);

        $this->assertTrue($policy->forceDeleteAny($user));
    }

    public function test_restore_method()
    {
        $user = $this->createMock(User::class);
        $model = $this->createMock(Crud::class);
        $policy = $this->getMockForAbstractClass(PolicyContract::class);

        $this->assertTrue($policy->restore($user, $model));
    }

    public function test_restore_any_method()
    {
        $user = $this->createMock(User::class);
        $policy = $this->getMockForAbstractClass(PolicyContract::class);

        $this->assertTrue($policy->restoreAny($user));
    }

    public function test_replicate_method()
    {
        $user = $this->createMock(User::class);
        $model = $this->createMock(Crud::class);
        $policy = $this->getMockForAbstractClass(PolicyContract::class);

        $this->assertTrue($policy->replicate($user, $model));
    }

    public function test_reorder_method()
    {
        $user = $this->createMock(User::class);
        $policy = $this->getMockForAbstractClass(PolicyContract::class);

        $this->assertTrue($policy->reorder($user));
    }
}
