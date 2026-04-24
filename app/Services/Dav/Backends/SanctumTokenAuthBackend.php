<?php

declare(strict_types=1);

namespace App\Services\Dav\Backends;

use App\Models\User;
use App\Services\Dav\Support\CurrentDavAuth;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Sabre\DAV\Auth\Backend\AbstractBasic;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Validates HTTP Basic credentials by treating the supplied password as a
 * Laravel Sanctum personal access token. The token must belong to a User and
 * carry the `dav:read` ability. Write operations additionally check
 * `dav:write` inside the CardDAV/CalDAV backends.
 */
class SanctumTokenAuthBackend extends AbstractBasic
{
    public const ABILITY_READ = 'dav:read';

    public const ABILITY_WRITE = 'dav:write';

    /**
     * Set on a successful validation so check() can build the principal URI
     * from the resolved user id rather than the (arbitrary) username.
     */
    private ?int $authenticatedUserId = null;

    public function __construct(private CurrentDavAuth $current)
    {
        $this->realm = config('app.name', 'spork').'/dav';
    }

    public function check(RequestInterface $request, ResponseInterface $response)
    {
        [$ok, $principalOrReason] = parent::check($request, $response);

        if (! $ok) {
            return [false, $principalOrReason];
        }

        return [true, 'principals/'.$this->authenticatedUserId];
    }

    protected function validateUserPass($username, $password)
    {
        $this->authenticatedUserId = null;

        if (! is_string($password) || $password === '') {
            return false;
        }

        $accessToken = PersonalAccessToken::findToken($password);

        if (! $accessToken) {
            return false;
        }

        if ($accessToken->expires_at && $accessToken->expires_at->isPast()) {
            return false;
        }

        if (! is_a($accessToken->tokenable_type, User::class, true)) {
            return false;
        }

        if (! $accessToken->can(self::ABILITY_READ)) {
            return false;
        }

        $user = $accessToken->tokenable;

        if (! $user instanceof User) {
            return false;
        }

        Auth::onceUsingId($user->getKey());
        /** @var \Laravel\Sanctum\Contracts\HasApiTokens|null $authenticated */
        $authenticated = Auth::user();
        if ($authenticated !== null) {
            $authenticated->withAccessToken($accessToken);
        }
        $accessToken->forceFill(['last_used_at' => now()])->save();

        $this->current->set($user, $accessToken);
        $this->authenticatedUserId = (int) $user->getKey();

        return true;
    }
}
