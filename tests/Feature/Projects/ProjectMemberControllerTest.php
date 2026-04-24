<?php

declare(strict_types=1);

namespace Tests\Feature\Projects;

use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\ProjectMembershipRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProjectMemberControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_invite_a_member_by_email(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->getKey()]);

        $response = $this->actingAs($owner)->post(
            route('projects.members.invite', $project),
            [
                'email' => $invitee->email,
                'role' => ProjectMembershipRole::EDITOR->value,
            ],
        );

        $response->assertRedirect(route('projects.members.index', $project));
        $this->assertDatabaseHas('project_memberships', [
            'project_id' => $project->getKey(),
            'user_id' => $invitee->getKey(),
            'role' => ProjectMembershipRole::EDITOR->value,
        ]);
    }

    public function test_invite_fails_for_unknown_email(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->getKey()]);

        $response = $this->actingAs($owner)->post(
            route('projects.members.invite', $project),
            [
                'email' => 'unknown@example.com',
                'role' => ProjectMembershipRole::VIEWER->value,
            ],
        );

        $response->assertNotFound();
    }

    public function test_non_owner_cannot_invite(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->getKey()]);

        $response = $this->actingAs($stranger)->post(
            route('projects.members.invite', $project),
            [
                'email' => 'someone@example.com',
                'role' => ProjectMembershipRole::VIEWER->value,
            ],
        );

        $response->assertForbidden();
    }

    public function test_owner_can_remove_a_member(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->getKey()]);

        ProjectMembership::query()->create([
            'project_id' => $project->getKey(),
            'user_id' => $member->getKey(),
            'role' => ProjectMembershipRole::VIEWER,
            'invited_at' => now(),
            'accepted_at' => now(),
        ]);

        $response = $this->actingAs($owner)->delete(
            route('projects.members.destroy', [$project, $member]),
        );

        $response->assertRedirect(route('projects.members.index', $project));
        $this->assertDatabaseMissing('project_memberships', [
            'project_id' => $project->getKey(),
            'user_id' => $member->getKey(),
        ]);
    }

    public function test_invite_validation_rejects_unknown_role(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->getKey()]);

        $response = $this->actingAs($owner)->post(
            route('projects.members.invite', $project),
            [
                'email' => $invitee->email,
                'role' => 'super-admin',
            ],
        );

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('project_memberships', [
            'project_id' => $project->getKey(),
            'user_id' => $invitee->getKey(),
        ]);
    }
}
