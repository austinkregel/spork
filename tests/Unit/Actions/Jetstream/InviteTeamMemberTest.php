<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Jetstream;

use App\Actions\Jetstream\InviteTeamMember;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Laravel\Jetstream\Events\InvitingTeamMember;
use Laravel\Jetstream\Mail\TeamInvitation;
use Tests\TestCase;

class InviteTeamMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_invite_team_member(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $email = 'newmember@example.com';

        Gate::shouldReceive('forUser')->with($user)->andReturnSelf();
        Gate::shouldReceive('authorize')->with('addTeamMember', $team)->andReturn(true);

        InvitingTeamMember::fake();
        Mail::fake();

        $action = new InviteTeamMember();
        $action->invite($user, $team, $email, 'admin');

        $this->assertDatabaseHas('team_invitations', [
            'team_id' => $team->id,
            'email' => $email,
            'role' => 'admin',
        ]);

        Mail::assertSent(TeamInvitation::class, function ($mail) use ($email) {
            return $mail->hasTo($email);
        });

        InvitingTeamMember::assertDispatched();
    }

    public function test_validate_invite_team_member(): void
    {
        $team = Team::factory()->create();
        $email = 'newmember@example.com';

        Validator::shouldReceive('make')->andReturnSelf();
        Validator::shouldReceive('after')->andReturnSelf();
        Validator::shouldReceive('validateWithBag')->andReturn(true);

        $action = new InviteTeamMember();
        $action->validate($team, $email, 'admin');

        Validator::shouldHaveReceived('make')->with([
            'email' => $email,
            'role' => 'admin',
        ], $action->rules($team), [
            'email.unique' => __('This user has already been invited to the team.'),
        ]);
    }
}
