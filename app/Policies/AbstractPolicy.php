<?php

declare(strict_types=1);

namespace App\Policies;

use App\Contracts\PolicyContract;
use App\Models\Crud;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AbstractPolicy implements PolicyContract
{
    use HandlesAuthorization;

    public const MODEL_PERMISSION_NAME = 'undefined';

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_'.static::MODEL_PERMISSION_NAME);
    }

    public function view(User $user, Crud $model): bool
    {
        return $user->can('view_'.static::MODEL_PERMISSION_NAME, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('create_'.static::MODEL_PERMISSION_NAME);
    }

    public function update(User $user, Crud $model): bool
    {
        return $user->can('update_'.static::MODEL_PERMISSION_NAME, $model);
    }

    public function delete(User $user, Crud $model): bool
    {
        return $user->can('delete_'.static::MODEL_PERMISSION_NAME, $model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_'.static::MODEL_PERMISSION_NAME);
    }

    public function forceDelete(User $user, Crud $model): bool
    {
        return $user->can('force_delete_'.static::MODEL_PERMISSION_NAME, $model);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_'.static::MODEL_PERMISSION_NAME);
    }

    public function restore(User $user, Crud $model): bool
    {
        return $user->can('restore_'.static::MODEL_PERMISSION_NAME, $model);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_'.static::MODEL_PERMISSION_NAME);
    }

    public function replicate(User $user, Crud $model): bool
    {
        return $user->can('replicate_'.static::MODEL_PERMISSION_NAME, $model);
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_'.static::MODEL_PERMISSION_NAME);
    }
}
