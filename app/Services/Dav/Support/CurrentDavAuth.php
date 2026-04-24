<?php

declare(strict_types=1);

namespace App\Services\Dav\Support;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Request-scoped store for the credentials Sabre's auth backend resolved.
 * Backends can pull the access token out without depending on Auth::user(),
 * which avoids any flakiness from session/state guard juggling inside Sabre.
 */
class CurrentDavAuth
{
    private ?User $user = null;

    private ?PersonalAccessToken $accessToken = null;

    public function set(User $user, PersonalAccessToken $accessToken): void
    {
        $this->user = $user;
        $this->accessToken = $accessToken;
    }

    public function clear(): void
    {
        $this->user = null;
        $this->accessToken = null;
    }

    public function user(): ?User
    {
        return $this->user;
    }

    public function accessToken(): ?PersonalAccessToken
    {
        return $this->accessToken;
    }

    public function can(string $ability): bool
    {
        return $this->accessToken !== null && $this->accessToken->can($ability);
    }
}
