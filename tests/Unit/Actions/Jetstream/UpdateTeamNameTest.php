<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Jetstream;

use App\Actions\Jetstream\UpdateTeamName;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateTeamNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_team_name(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $input = ['name' => 'New Team Name'];

        Gate::shouldReceive('forUser')->with($user)->andReturnSelf();
        Gate::shouldReceive('authorize')->with('update', $team)->andReturn(true);

        Validator::shouldReceive('make')->andReturnSelf();
        Validator::shouldReceive('validateWithBag')->andReturn(true);

        $action = new UpdateTeamName();
        $action->update($user, $team, $input);

        $this->assertEquals('New Team Name', $team->fresh()->name);
    }
}
