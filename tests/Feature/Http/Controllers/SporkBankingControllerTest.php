<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Finance\Budget;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkBankingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_banking_route_is_accessible()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/finance/banking');

        $response->assertStatus(200);
    }

    public function test_banking_budgets_route_is_accessible()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/finance/banking/budgets');

        $response->assertStatus(200);
    }

    public function test_banking_route_loads_expected_data()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/finance/banking');

        $response->assertInertia(fn ($page) => $page
            ->component('Banking/Index')
            ->where('tab', 'overview')
            ->has('overview'));
    }

    public function test_banking_budgets_route_loads_expected_data()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/finance/banking/budgets');

        $response->assertInertia(fn ($page) => $page
            ->component('Banking/Index')
            ->where('tab', 'budgets')
            ->has('budgetsData'));
    }

    public function test_banking_budgets_include_percent_of_income_from_person_estimated_income(): void
    {
        $user = User::factory()->create();
        Person::factory()->create([
            'user_id' => $user->id,
            'estimated_income' => '120000',
        ]);

        Budget::factory()->create([
            'user_id' => $user->id,
            'name' => 'Housing',
            'amount' => 1000,
            'frequency' => Budget::FREQUENCY_MONTHLY,
            'interval' => 1,
        ]);

        $response = $this->actingAs($user)->get('http://spork.localhost/-/finance/banking/budgets');

        $response->assertInertia(fn ($page) => $page
            ->component('Banking/Index')
            ->where('tab', 'budgets')
            ->has('budgetsData.budgets')
            ->where('budgetsData.budgets.0.net_monthly_income', 7500)
            ->where('budgetsData.budgets.0.expected_percent_of_income', 13.33)
            ->where('budgetsData.budgets.0.actual_percent_of_income', 0));
    }
}
