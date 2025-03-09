<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Fortify;

use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ResetUserPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_user_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $action = new ResetUserPassword();

        $input = [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ];

        $action->reset($user, $input);

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }
}
