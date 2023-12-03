<?php

namespace App\Services\Development;

use App\Services\ActionFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DescribeTableService
{
    public function describe(Model $model): array
    {
        $mapField = fn ($everything) => array_values(array_map(fn ($query) => $query->Field, $everything));
        $description = cache()->remember('description.'.get_class($model), now()->addHour(), fn () => DB::select('describe '.(new $model)->getTable()));
        $indexes = cache()->remember('indexes.'.get_class($model), now()->addHour(), fn () => DB::select('show indexes from '.(new $model)->getTable()));
        $fields = $mapField($description);
        $sorts  = array_filter($description, function($query) {
            if (str_contains($query->Type, 'int') && $query->Null == 'NO') {
                return true;
            }

            if (Str::contains($query->Type, [
                'timestamp',
                'date',
            ]) && $query->Null == 'NO') {
                return true;
            }

            if (Str::contains($query->Field, [
                'name',
                'created_at',
                'deleted_at',
                'updated_at',
            ])) {
                return true;
            }

            return false;
        });

        $returnTypes = array_reduce(get_class_methods($model), function ($allClassMethods, $method) use ($model) {
            $ref = new \ReflectionMethod($model, $method);

            $type = $ref->getReturnType();
            if (empty($type)) {
                return $allClassMethods;
            }

            return array_merge($allClassMethods,
                [
                    $method => $type,
                ]);
        }, []);

        $methodsThatReturnAClass = array_filter($returnTypes, fn (\ReflectionNamedType $type) => class_exists($type->getName()));
        $relations = array_filter($methodsThatReturnAClass, function ($type) {
            $c = new \ReflectionClass($type->getName());

            if (! empty($parentClass = $c->getParentClass())) {
                if (! empty($parentParentClass = $parentClass->getParentClass())) {
                    if ($parentParentClass->getName() === \Illuminate\Database\Eloquent\Relations\Relation::class) {
                        return true;
                    }
                }
                if ($parentClass->getName() === \Illuminate\Database\Eloquent\Relations\Relation::class) {
                    return true;
                }
            }

            return false;
        });

        return [
            'actions' => ActionFilter::WHITELISTED_ACTIONS,
            'fillable' => empty($model->getFillable()) ? ['name'] :$model->getFillable(),
            'fields' => $fields,
            'filters' => array_map(fn ($query) => $query->Column_name, $indexes),
            'includes' => array_keys($relations),
            'sorts' => $mapField($sorts),
            'required' => $mapField(array_filter($description, fn ($query) => $query->Null === "NO" && $query->Extra !== 'auto_increment')),
        ];
    }


    public function describeTable(string $table): array
    {
        $mapField = fn ($everything) => array_values(array_map(fn ($query) => $query->Field, $everything));
        $description = cache()->remember('description.'.$table, now()->addHour(), fn () => DB::select('describe '.$table));
        $indexes = cache()->remember('indexes.'.$table, now()->addHour(), fn () => DB::select('show indexes from '.$table));
        $fields = $mapField($description);
        $sorts  = array_filter($description, function($query) {
            if (str_contains($query->Type, 'int') && $query->Null == 'NO') {
                return true;
            }

            if (Str::contains($query->Type, [
                'timestamp',
                'date',
            ]) && $query->Null == 'NO') {
                return true;
            }

            if (Str::contains($query->Field, [
                'name',
                'created_at',
                'deleted_at',
                'updated_at',
            ])) {
                return true;
            }

            return false;
        });

        $returnTypes = [];

        $methodsThatReturnAClass = array_filter($returnTypes, fn (\ReflectionNamedType $type) => class_exists($type->getName()));
        $relations = array_filter($methodsThatReturnAClass, function ($type) {
            $c = new \ReflectionClass($type->getName());

            if (! empty($parentClass = $c->getParentClass())) {
                if (! empty($parentParentClass = $parentClass->getParentClass())) {
                    if ($parentParentClass->getName() === \Illuminate\Database\Eloquent\Relations\Relation::class) {
                        return true;
                    }
                }
                if ($parentClass->getName() === \Illuminate\Database\Eloquent\Relations\Relation::class) {
                    return true;
                }
            }

            return false;
        });

        return [
            'actions' => ActionFilter::WHITELISTED_ACTIONS,
            'fillable' => ['name'],
            'fields' => $fields,
            'filters' => array_map(fn ($query) => $query->Column_name, $indexes),
            'includes' => array_keys($relations),
            'sorts' => $mapField($sorts),
            'required' => $mapField(array_filter($description, fn ($query) => $query->Null === "NO" && $query->Extra !== 'auto_increment')),
        ];
    }
}
