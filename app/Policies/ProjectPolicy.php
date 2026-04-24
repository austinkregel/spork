<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Crud;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\User;

class ProjectPolicy extends AbstractPolicy
{
    public const MODEL_PERMISSION_NAME = 'project';

    public function view(User $user, Crud $model): bool
    {
        if (! $model instanceof Project) {
            return parent::view($user, $model);
        }

        if ($model->isOwnedBy($user)) {
            return true;
        }

        if ($model->hasAcceptedMember($user)) {
            return true;
        }

        return parent::view($user, $model);
    }

    public function update(User $user, Crud $model): bool
    {
        if (! $model instanceof Project) {
            return parent::update($user, $model);
        }

        if ($model->isOwnedBy($user)) {
            return true;
        }

        $membership = $model->membershipFor($user);

        if ($membership instanceof ProjectMembership && $membership->isAccepted() && $membership->canEdit()) {
            return true;
        }

        return parent::update($user, $model);
    }

    public function delete(User $user, Crud $model): bool
    {
        if (! $model instanceof Project) {
            return parent::delete($user, $model);
        }

        return $model->isOwnedBy($user) || parent::delete($user, $model);
    }

    public function attach(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    public function detach(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    public function seeAttachment(User $user, Project $project): bool
    {
        return $this->view($user, $project);
    }

    public function invite(User $user, Project $project): bool
    {
        if ($project->isOwnedBy($user)) {
            return true;
        }

        $membership = $project->membershipFor($user);

        return $membership instanceof ProjectMembership
            && $membership->isAccepted()
            && $membership->canManageMembers();
    }

    public function removeMember(User $user, Project $project, User $member): bool
    {
        if ($project->isOwnedBy($member)) {
            return false;
        }

        if ($user->getKey() === $member->getKey()) {
            return true;
        }

        return $this->invite($user, $project);
    }
}
