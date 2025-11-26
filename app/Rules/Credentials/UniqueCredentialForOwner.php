<?php

declare(strict_types=1);

namespace App\Rules\Credentials;

use App\Models\Credential;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class UniqueCredentialForOwner implements ValidationRule, DataAwareRule
{
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    public function __construct(
        private readonly User $user,
    ) {
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $service = $this->data['service'] ?? null;

        if ($service === null || $service === '') {
            // We only enforce uniqueness when the service is known.
            return;
        }

        // Build the fingerprint from the full credential payload rather than a single field.
        $fingerprint = Credential::makeSecretFingerprint(
            api_key: $this->data['api_key'] ?? null,
            secret_key: $this->data['secret_key'] ?? null,
            access_token: $this->data['access_token'] ?? null,
            refresh_token: $this->data['refresh_token'] ?? null,
            settings: $this->data['settings'] ?? null,
        );

        if ($fingerprint === null) {
            // No meaningful secret provided; skip uniqueness enforcement.
            return;
        }

        $query = Credential::query()
            ->where('service', $service)
            ->where('secret_fingerprint', $fingerprint);

        $this->applyOwnerScope($query);

        /** @var \App\Models\Credential|null $existing */
        $existing = $query->first();

        if (! $existing) {
            return;
        }

        $fail(__('A credential with these secret values already exists.'));

        // Surface the existing credential details for the UI without exposing secrets.
        request()->merge([
            '_existing_credential' => [
                'id' => $existing->id,
                'name' => $existing->name,
                'route' => route('manage.show', ['slug' => $existing->getTable()]),
            ],
        ]);
    }

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    private function applyOwnerScope(Builder $query): void
    {
        $userId = $this->user->getKey();

        // Look up all team IDs the user belongs to either as an owner or as a member.
        $teamIds = DB::table('team_user')
            ->where('user_id', $userId)
            ->pluck('team_id')
            ->merge(
                DB::table('teams')
                    ->where('user_id', $userId)
                    ->pluck('id')
            )
            ->unique()
            ->values();

        if ($teamIds->isEmpty()) {
            // Fall back to per-user scope when the user is not a member of any team.
            $query->where('user_id', $userId);

            return;
        }

        // Within the team scope, any user that belongs to at least one of the
        // same teams is considered an "owner" of the secret. This includes both
        // team owners and members connected via the team_user pivot.
        $userIds = DB::table('team_user')
            ->whereIn('team_id', $teamIds)
            ->pluck('user_id')
            ->merge(
                DB::table('teams')
                    ->whereIn('id', $teamIds)
                    ->pluck('user_id')
            )
            ->unique()
            ->values();

        $query->whereIn('user_id', $userIds);
    }
}


