<?php

declare(strict_types=1);

namespace App\Services\Condition;

use App\Models\User;

class HasRoleOperator extends AbstractLogicalOperator
{
    public function compute(mixed $user, mixed $role): bool
    {
        return $user->hasRole($role);
    }
}
