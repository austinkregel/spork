<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkBankingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_banking_route_is_accessible()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/banking');

        $response->assertStatus(200);
    }

    public function test_banking_budgets_route_is_accessible()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/banking/budgets');

        $response->assertStatus(200);
    }

    public function test_banking_route_loads_expected_data()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/banking');

        $response->assertInertia(fn ($page) => $page
            ->component('Banking/Index')
            ->has('accounts')
            ->has('transactions')
        );
    }

    public function test_banking_budgets_route_loads_expected_data()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/banking/budgets');

        $response->assertInertia(fn ($page) => $page
            ->component('Banking/Index')
            ->has('accounts')
        );
    }
}
