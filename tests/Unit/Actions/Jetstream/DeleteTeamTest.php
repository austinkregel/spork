<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Jetstream;

use App\Actions\Jetstream\DeleteTeam;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_team(): void
    {
        $team = Team::factory()->create();

        $action = new DeleteTeam();
        $action->delete($team);

        $this->assertNull(Team::find($team->id));
    }
}
