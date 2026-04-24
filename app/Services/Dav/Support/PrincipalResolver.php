<?php

declare(strict_types=1);

namespace App\Services\Dav\Support;

use App\Models\User;

/**
 * Tiny helper to turn a "principals/{id}" URI into a User instance.
 */
class PrincipalResolver
{
    public function resolveUser(string $principalUri): ?User
    {
        if (! str_starts_with($principalUri, 'principals/')) {
            return null;
        }

        $id = substr($principalUri, strlen('principals/'));

        if (! ctype_digit($id)) {
            return null;
        }

        return User::query()->find((int) $id);
    }
}
