<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;

/*
 * When a model is created, we want to ensure that we create the respective CRUD perms when a model is created,
 *  and delete them when they model they're based on is deleted.
 */
class ApplyCredentialsObserver
{
    protected $permissions = [
        'view' => 'view_',
        'update' => 'update_',
        'delete' => 'delete_',
        'restore' => 'restore_',
    ];

    public function created($modelThatWillHavePermissionsCreatedForIt): void
    {
        $this->createPermissionsForModel($modelThatWillHavePermissionsCreatedForIt);
    }

    public function deleted($modelThatHadPermissionsCreatedForIt): void
    {
        $this->deletePermissionsForModel($modelThatHadPermissionsCreatedForIt);
    }

    public function restored($modelThatHadPermissionsCreatedForIt): void
    {
        $this->createPermissionsForModel($modelThatHadPermissionsCreatedForIt);
    }

    protected function createPermission($basePermission, Model $model): Permission
    {
        return Permission::firstOrCreate([
            'name' => $basePermission.$model->getTable().'.'.$model->id,
        ], [
            'group' => $model->getTable(),
            'guard_name' => 'web',
        ]);
    }

    protected function createPermissionsForModel(Model $model)
    {
        /** @var User $user */
        $user = auth()->check() ? auth()->user() : null;

        foreach ($this->permissions as $basePermission) {
            $this->createPermission($basePermission, $model);
        }
    }

    protected function deletePermissionsForModel(Model $model)
    {
        foreach ($this->permissions as $basePermission) {
            $perm = Permission::findByName($basePermission.$model->getTable().'.'.$model->id);

            if (empty($perm)) {
                continue;
            }

            $perm->delete();
        }
    }
}
