<?php

declare(strict_types=1);

namespace App\Services\Development;

use App\Contracts\ActionInterface;
use App\Services\ActionFilter;
use App\Services\Code;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DescribeTableService
{
    public function describe(Model $model): array
    {
        $mapField = fn ($everything) => array_values(array_map(fn ($query) => $query->Field, $everything));
        $description = cache()->remember(
            'description.'.get_class($model),
            now()->addHour(),
            fn () => DB::select('describe '.(new $model)->getTable())
        );
        $indexes = cache()->remember(
            'indexes.'.get_class($model),
            now()->addHour(),
            fn () => DB::select('show indexes from '.(new $model)->getTable())
        );
        $fields = $mapField($description);
        $sorts = array_filter($description, function ($query) {
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

        $methodsThatReturnAClass = array_filter($returnTypes, function (\ReflectionNamedType|\ReflectionUnionType $type) {
            if ($type instanceof \ReflectionUnionType) {
                $allTypes = $type->getTypes();

                /** @var \ReflectionNamedType $t */
                foreach ($allTypes as $t) {
                    if (! class_exists($t->getName())) {
                        return false;
                    }
                }
            }

            return class_exists($type->getName());
        });
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
        $fillable = empty($model->getFillable()) ? ['name'] : $model->getFillable();

        //        $actions = Code::instancesOf(ActionInterface::class)->getClasses();
        //
        //        dd(array_map(fn ($q) => new $q, $actions));

        return [
            'name' => $model->getTable(),
            'model' => get_class($model),
            'pretty_name' => class_basename(get_class($model)),
            'actions' => array_map(fn ($class) => (array) (new $class), $model->actions ?? []),
            'query_actions' => ActionFilter::WHITELISTED_ACTIONS,
            'fillable' => $fillable,
            'fields' => $fields,
            'filters' => array_map(fn ($query) => $query->Column_name, $indexes),
            'includes' => array_keys($relations),
            'sorts' => $mapField($sorts),
            'types' => array_reduce($description, function ($allFields, $field) {
                $simpleType = explode('(', $field->Type, 2);

                if (count($simpleType) > 1) {
                    $possibleLimit = explode(')', $simpleType[1], 2);
                }

                return array_merge(
                    $allFields,
                    [
                        $field->Field => array_merge([
                            'type' => match ($simpleType[0]) {
                                'bigint' => 'number',
                                'varchar' => 'text',
                                'longtext' => 'textarea',
                                'datetime', 'timestamp' => 'datetime',

                                default => $simpleType[0]
                            },
                        ], $field->Default ? [
                            'value' => $field->Default,
                        ] : [],
                            isset($possibleLimit) ? [
                                'max-length' => $possibleLimit[0],
                            ] : [],
                        ),
                    ]
                );
            }, []),
            'required' => $mapField(array_filter($description, fn ($query) => $query->Null === 'NO' && $query->Extra !== 'auto_increment')),
        ];
    }

    public function describeTable(string $table): array
    {
        $mapField = fn ($everything) => array_values(array_map(fn ($query) => $query->Field, $everything));
        $description = cache()->remember('description.'.$table, now()->addHour(), fn () => DB::select('describe '.$table));
        $indexes = cache()->remember('indexes.'.$table, now()->addHour(), fn () => DB::select('show indexes from '.$table));
        $fields = $mapField($description);
        $sorts = array_filter($description, function ($query) {
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
            'name' => $table,
            'actions' => ActionFilter::WHITELISTED_ACTIONS,
            'fillable' => ['name'],
            'fields' => $fields,
            'filters' => array_map(fn ($query) => $query->Column_name, $indexes),
            'includes' => array_keys($relations),
            'sorts' => $mapField($sorts),
            'required' => $mapField(array_filter($description, fn ($query) => $query->Null === 'NO' && $query->Extra !== 'auto_increment')),
        ];
    }
}
