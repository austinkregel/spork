<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Crud;
use App\Models\User;

interface PolicyContract
{
    public function viewAny(User $user): bool;

    public function view(User $user, Crud $model): bool;

    public function create(User $user): bool;

    public function update(User $user, Crud $model): bool;

    public function delete(User $user, Crud $model): bool;

    public function deleteAny(User $user): bool;

    public function forceDelete(User $user, Crud $model): bool;

    public function forceDeleteAny(User $user): bool;

    public function restore(User $user, Crud $model): bool;

    public function restoreAny(User $user): bool;

    public function replicate(User $user, Crud $model): bool;

    public function reorder(User $user): bool;
}
