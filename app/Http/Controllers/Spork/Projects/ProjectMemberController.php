<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\InviteProjectMemberRequest;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\ProjectMembershipRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProjectMemberController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        Gate::authorize('view', $project);

        $project->load(['memberships.user', 'user']);

        return Inertia::render('Pillars/Projects/Members', [
            'project' => [
                'id' => $project->getKey(),
                'name' => $project->name,
                'owner' => $project->user?->only(['id', 'name', 'email', 'profile_photo_url']),
            ],
            'members' => $project->memberships->map(fn (ProjectMembership $membership) => [
                'id' => $membership->getKey(),
                'role' => $membership->role?->value,
                'role_label' => $membership->role?->label(),
                'invited_at' => $membership->invited_at?->toIso8601String(),
                'accepted_at' => $membership->accepted_at?->toIso8601String(),
                'user' => $membership->user?->only(['id', 'name', 'email', 'profile_photo_url']),
            ])->values(),
            'available_roles' => array_map(
                static fn (ProjectMembershipRole $role) => ['value' => $role->value, 'label' => $role->label()],
                ProjectMembershipRole::cases(),
            ),
            'can' => [
                'invite' => $request->user()?->can('invite', $project) ?? false,
            ],
        ]);
    }

    public function invite(InviteProjectMemberRequest $request, Project $project): RedirectResponse
    {
        $data = $request->validated();

        $invitee = User::query()->where('email', $data['email'])->first();
        abort_if($invitee === null, 404, 'No account found for that email.');
        abort_if($project->isOwnedBy($invitee), 422, 'The owner is already a project member.');

        ProjectMembership::query()->updateOrCreate(
            [
                'project_id' => $project->getKey(),
                'user_id' => $invitee->getKey(),
            ],
            [
                'role' => ProjectMembershipRole::from($data['role']),
                'invited_at' => now(),
            ],
        );

        return redirect()
            ->route('projects.members.index', $project)
            ->with('status', 'Invitation sent.');
    }

    public function destroy(Request $request, Project $project, User $member): RedirectResponse
    {
        Gate::authorize('removeMember', [$project, $member]);

        ProjectMembership::query()
            ->where('project_id', $project->getKey())
            ->where('user_id', $member->getKey())
            ->delete();

        return redirect()
            ->route('projects.members.index', $project)
            ->with('status', 'Member removed.');
    }
}
