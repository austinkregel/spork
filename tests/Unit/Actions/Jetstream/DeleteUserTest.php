<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Jetstream;

use App\Actions\Jetstream\DeleteUser;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Contracts\DeletesTeams;
use Tests\TestCase;

class DeleteUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_user(): void
    {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);

        $deletesTeams = $this->createMock(DeletesTeams::class);
        $deletesTeams->expects($this->once())
            ->method('delete')
            ->with($team);

        $action = new DeleteUser($deletesTeams);
        $action->delete($user);

        $this->assertNull(User::find($user->id));
    }
}
