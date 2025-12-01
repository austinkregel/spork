<?php

declare(strict_types=1);

namespace App\Services\Development\DescribeTable;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PermissionAdvisor
{
    public function __construct(
        private readonly AuthFactory $auth,
    ) {
    }

    public function forModel(Model $model): array
    {
        $user = $this->auth->guard()->user();

        if (! $user instanceof Authenticatable) {
            return [];
        }

        $table = Str::singular($model->getTable());

        return [
            'create' => $this->check($user, 'create_'.$table),
            'update' => $this->check($user, 'update_'.$table),
            'delete' => $this->check($user, 'delete_'.$table),
            'delete_any' => $this->check($user, 'delete_any_'.$table),
        ];
    }

    private function check(Authenticatable $user, string $ability): bool
    {
        if (method_exists($user, 'can') && $user->can($ability)) {
            return true;
        }

        return $this->userIsDeveloper($user);
    }

    private function userIsDeveloper(Authenticatable $user): bool
    {
        return method_exists($user, 'hasRole') && $user->hasRole('developer');
    }
}

