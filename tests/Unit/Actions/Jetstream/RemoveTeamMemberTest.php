<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Jetstream;

use App\Actions\Jetstream\RemoveTeamMember;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Jetstream\Events\TeamMemberRemoved;
use Tests\TestCase;

class RemoveTeamMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_remove_team_member(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $teamMember = User::factory()->create();

        $team->users()->attach($teamMember, ['role' => 'admin']);

        Event::fake();

        $action = new RemoveTeamMember();
        $action->remove($user, $team, $teamMember);

        $this->assertFalse($team->fresh()->hasUserWithEmail($teamMember->email));
        Event::assertDispatched(TeamMemberRemoved::class);
    }

    public function test_remove_team_member_unauthorized(): void
    {
        $this->expectException(AuthorizationException::class);

        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $teamMember = User::factory()->create();

        $team->users()->attach($teamMember, ['role' => 'admin']);

        $action = new RemoveTeamMember();
        $action->remove($teamMember, $team, $user);
    }

    public function test_remove_team_member_ensure_user_does_not_own_team(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);

        $action = new RemoveTeamMember();
        $action->remove($user, $team, $user);
    }
}
