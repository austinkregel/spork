<?php

declare(strict_types=1);

namespace Tests\Feature\Projects;

use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\ProjectMembershipRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProjectPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_update_delete_invite(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->getKey()]);

        $this->assertTrue($owner->can('view', $project));
        $this->assertTrue($owner->can('update', $project));
        $this->assertTrue($owner->can('delete', $project));
        $this->assertTrue($owner->can('invite', $project));
        $this->assertTrue($owner->can('attach', $project));
    }

    public function test_unrelated_user_cannot_view_or_update(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->getKey()]);

        $stranger = User::factory()->create();

        $this->assertFalse($stranger->can('view', $project));
        $this->assertFalse($stranger->can('update', $project));
        $this->assertFalse($stranger->can('invite', $project));
    }

    public function test_accepted_editor_can_update_but_not_invite(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->getKey()]);
        $editor = User::factory()->create();

        ProjectMembership::query()->create([
            'project_id' => $project->getKey(),
            'user_id' => $editor->getKey(),
            'role' => ProjectMembershipRole::EDITOR,
            'invited_at' => now(),
            'accepted_at' => now(),
        ]);

        $this->assertTrue($editor->can('view', $project));
        $this->assertTrue($editor->can('update', $project));
        $this->assertTrue($editor->can('attach', $project));
        $this->assertFalse($editor->can('invite', $project));
        $this->assertFalse($editor->can('delete', $project));
    }

    public function test_pending_invitation_does_not_grant_access(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->getKey()]);
        $invitee = User::factory()->create();

        ProjectMembership::query()->create([
            'project_id' => $project->getKey(),
            'user_id' => $invitee->getKey(),
            'role' => ProjectMembershipRole::EDITOR,
            'invited_at' => now(),
        ]);

        $this->assertFalse($invitee->can('view', $project));
        $this->assertFalse($invitee->can('update', $project));
    }

    public function test_viewer_cannot_update(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->getKey()]);
        $viewer = User::factory()->create();

        ProjectMembership::query()->create([
            'project_id' => $project->getKey(),
            'user_id' => $viewer->getKey(),
            'role' => ProjectMembershipRole::VIEWER,
            'invited_at' => now(),
            'accepted_at' => now(),
        ]);

        $this->assertTrue($viewer->can('view', $project));
        $this->assertFalse($viewer->can('update', $project));
    }

    public function test_member_can_remove_themselves_but_not_owner(): void
    {
        $owner = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $owner->getKey()]);
        $member = User::factory()->create();

        ProjectMembership::query()->create([
            'project_id' => $project->getKey(),
            'user_id' => $member->getKey(),
            'role' => ProjectMembershipRole::EDITOR,
            'invited_at' => now(),
            'accepted_at' => now(),
        ]);

        $this->assertTrue($member->can('removeMember', [$project, $member]));
        $this->assertFalse($member->can('removeMember', [$project, $owner]));
    }
}
