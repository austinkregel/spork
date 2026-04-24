<?php

declare(strict_types=1);

namespace Tests\Feature\Banking;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BankingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_overview_page_renders_with_base_payload(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('http://spork.localhost/-/finance/banking')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Banking/Index')
                ->where('tab', 'overview')
                ->has('overview')
                ->has('navigation'));
    }

    public function test_accounts_page_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('http://spork.localhost/-/finance/banking/accounts')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Banking/Index')
                ->where('tab', 'accounts')
                ->has('accountsData'));
    }
}
