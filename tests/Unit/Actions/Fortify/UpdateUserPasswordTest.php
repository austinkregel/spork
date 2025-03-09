<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Fortify;

use App\Actions\Fortify\UpdateUserPassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UpdateUserPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_user_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $action = new UpdateUserPassword();

        $input = [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ];

        $action->update($user, $input);

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_current_password_must_be_correct(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $action = new UpdateUserPassword();

        $input = [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ];

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $action->update($user, $input);

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_new_passwords_must_match(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $action = new UpdateUserPassword();

        $input = [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'wrong-password',
        ];

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $action->update($user, $input);

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }
}
