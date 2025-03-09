<?php

declare(strict_types=1);

namespace Tests\Unit\Contracts\Repositories;

use App\Contracts\Repositories\CredentialRepositoryContract;
use App\Models\Credential;
use App\Repositories\CredentialRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CredentialRepositoryContractTest extends TestCase
{
    use RefreshDatabase;

    private CredentialRepositoryContract $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new CredentialRepository();
    }

    public function test_find_all_of_type_returns_length_aware_paginator(): void
    {
        Credential::factory()->count(20)->create(['type' => 'test']);

        $result = $this->repository->findAllOfType('test');

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(15, $result->items());
    }

    public function test_find_all_of_type_paginates_correctly(): void
    {
        Credential::factory()->count(20)->create(['type' => 'test']);

        $result = $this->repository->findAllOfType('test', 10, 2);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(10, $result->items());
        $this->assertEquals(2, $result->currentPage());
    }
}
