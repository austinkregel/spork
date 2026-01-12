<?php

declare(strict_types=1);

namespace Tests\Feature\Banking;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankingPreferenceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_account_pins(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('http://spork.localhost/-/banking/settings')
            ->put('http://spork.localhost/-/banking/preferences/pins', [
                'type' => 'accounts',
                'order' => ['acc_1', 'acc_2'],
            ])
            ->assertRedirect('http://spork.localhost/-/banking/settings');

        $this->assertDatabaseHas('banking_preferences', [
            'user_id' => $user->id,
        ]);

        $this->assertSame(['acc_1', 'acc_2'], $user->fresh()->bankingPreference->pinned_accounts);
    }
}
