<?php

declare(strict_types=1);

namespace App\Services\Dav\Backends;

use App\Models\User;
use Sabre\DAV\PropPatch;
use Sabre\DAVACL\PrincipalBackend\BackendInterface;

/**
 * Maps DAV principals 1:1 to App\Models\User. Each user has a single
 * principal at "principals/{user_id}".
 */
class UserPrincipalBackend implements BackendInterface
{
    public const PREFIX = 'principals';

    public function getPrincipalsByPrefix($prefixPath)
    {
        if (! $this->matchesPrefix($prefixPath)) {
            return [];
        }

        return User::query()->get()->map(fn (User $user) => $this->principalArray($user))->all();
    }

    public function getPrincipalByPath($path)
    {
        $user = $this->resolveUser($path);

        return $user ? $this->principalArray($user) : null;
    }

    public function updatePrincipal($path, PropPatch $propPatch)
    {
        // Principals are read-only; we let PropPatch fail any properties the
        // client tries to mutate by simply not handling them.
    }

    public function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof')
    {
        if (! $this->matchesPrefix($prefixPath) || $searchProperties === []) {
            return [];
        }

        $query = User::query();

        foreach ($searchProperties as $property => $value) {
            $needle = '%'.$value.'%';
            $matchTest = $test === 'anyof' ? 'orWhere' : 'where';

            switch ($property) {
                case '{http://sabredav.org/ns}email-address':
                case '{DAV:}email-address':
                    $query->{$matchTest}('email', 'like', $needle);
                    break;
                case '{DAV:}displayname':
                    $query->{$matchTest}('name', 'like', $needle);
                    break;
                default:
                    return [];
            }
        }

        return $query->get()->map(fn (User $user) => self::PREFIX.'/'.$user->getKey())->all();
    }

    public function findByUri($uri, $principalPrefix)
    {
        if (! str_starts_with($uri, 'mailto:')) {
            return null;
        }

        $email = substr($uri, strlen('mailto:'));
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            return null;
        }

        return self::PREFIX.'/'.$user->getKey();
    }

    public function getGroupMemberSet($principal)
    {
        return [];
    }

    public function getGroupMembership($principal)
    {
        return [];
    }

    public function setGroupMemberSet($principal, array $members)
    {
        // Groups are not supported.
    }

    private function principalArray(User $user): array
    {
        return [
            'id' => $user->getKey(),
            'uri' => self::PREFIX.'/'.$user->getKey(),
            '{DAV:}displayname' => $user->name,
            '{http://sabredav.org/ns}email-address' => $user->email,
        ];
    }

    private function resolveUser(string $path): ?User
    {
        if (! str_starts_with($path, self::PREFIX.'/')) {
            return null;
        }

        $id = substr($path, strlen(self::PREFIX.'/'));

        if (! ctype_digit($id)) {
            return null;
        }

        return User::query()->find((int) $id);
    }

    private function matchesPrefix(string $prefixPath): bool
    {
        return rtrim($prefixPath, '/') === self::PREFIX;
    }
}
