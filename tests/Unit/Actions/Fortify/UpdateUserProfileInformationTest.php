<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Fortify;

use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Tests\TestCase;

class UpdateUserProfileInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_user_profile_information(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $action = new UpdateUserProfileInformation();

        $input = [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ];

        $action->update($user, $input);

        $this->assertEquals('New Name', $user->fresh()->name);
        $this->assertEquals('new@example.com', $user->fresh()->email);
    }

    public function test_update_user_profile_information_with_photo(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $action = new UpdateUserProfileInformation();

        $input = [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'photo' => 'photo.jpg',
        ];

        $user->expects('updateProfilePhoto')->with('photo.jpg');

        $action->update($user, $input);

        $this->assertEquals('New Name', $user->fresh()->name);
        $this->assertEquals('new@example.com', $user->fresh()->email);
    }

    public function test_update_verified_user_profile_information(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'email_verified_at' => now(),
        ]);

        $action = new UpdateUserProfileInformation();

        $input = [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ];

        $user->expects('sendEmailVerificationNotification');

        $action->update($user, $input);

        $this->assertEquals('New Name', $user->fresh()->name);
        $this->assertEquals('new@example.com', $user->fresh()->email);
        $this->assertNull($user->fresh()->email_verified_at);
    }
}
