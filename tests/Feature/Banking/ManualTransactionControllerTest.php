<?php

declare(strict_types=1);

namespace Tests\Feature\Banking;

use App\Models\Credential;
use App\Models\Finance\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_manual_transaction(): void
    {
        $user = User::factory()->create();
        /** @var Credential $credential */
        $credential = Credential::factory()->create(['user_id' => $user->id]);
        /** @var Account $account */
        $account = Account::factory()->create([
            'credential_id' => $credential->id,
            'account_id' => 'manual-account',
        ]);

        $this->actingAs($user)
            ->post('http://spork.localhost/-/banking/manual-transactions', [
                'account_id' => $account->account_id,
                'name' => 'Manual Entry',
                'amount' => -42.5,
                'date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('transactions', [
            'name' => 'Manual Entry',
            'account_id' => $account->account_id,
            'transaction_type' => 'manual',
        ]);
    }
}
