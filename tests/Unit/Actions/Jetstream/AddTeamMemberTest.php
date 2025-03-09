<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Jetstream;

use App\Actions\Jetstream\AddTeamMember;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\Events\AddingTeamMember;
use Laravel\Jetstream\Events\TeamMemberAdded;
use Tests\TestCase;

class AddTeamMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_team_member(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $newMember = User::factory()->create();

        Gate::shouldReceive('forUser')->with($user)->andReturnSelf();
        Gate::shouldReceive('authorize')->with('addTeamMember', $team)->andReturn(true);

        Jetstream::shouldReceive('findUserByEmailOrFail')->with($newMember->email)->andReturn($newMember);

        AddingTeamMember::fake();
        TeamMemberAdded::fake();

        $action = new AddTeamMember();
        $action->add($user, $team, $newMember->email, 'admin');

        $this->assertTrue($team->fresh()->hasUserWithEmail($newMember->email));
        $this->assertEquals('admin', $team->users()->where('email', $newMember->email)->first()->pivot->role);

        AddingTeamMember::assertDispatched();
        TeamMemberAdded::assertDispatched();
    }

    public function test_validate_add_team_member(): void
    {
        $team = Team::factory()->create();
        $newMember = User::factory()->create();

        Validator::shouldReceive('make')->andReturnSelf();
        Validator::shouldReceive('after')->andReturnSelf();
        Validator::shouldReceive('validateWithBag')->andReturn(true);

        $action = new AddTeamMember();
        $action->validate($team, $newMember->email, 'admin');

        Validator::shouldHaveReceived('make')->with([
            'email' => $newMember->email,
            'role' => 'admin',
        ], $action->rules(), [
            'email.exists' => __('We were unable to find a registered user with this email address.'),
        ]);
    }
}
