<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Jetstream;

use App\Actions\Jetstream\CreateTeam;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Laravel\Jetstream\Events\AddingTeam;
use Laravel\Jetstream\Jetstream;
use Tests\TestCase;

class CreateTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_team(): void
    {
        $user = User::factory()->create();
        $input = ['name' => 'Test Team'];

        Gate::shouldReceive('forUser')->with($user)->andReturnSelf();
        Gate::shouldReceive('authorize')->with('create', Jetstream::newTeamModel())->andReturn(true);

        Validator::shouldReceive('make')->andReturnSelf();
        Validator::shouldReceive('validateWithBag')->andReturn(true);

        AddingTeam::fake();

        $action = new CreateTeam();
        $team = $action->create($user, $input);

        $this->assertInstanceOf(Team::class, $team);
        $this->assertEquals('Test Team', $team->name);
        $this->assertFalse($team->personal_team);
        $this->assertTrue($user->fresh()->isCurrentTeam($team));

        AddingTeam::assertDispatched();
    }
}
