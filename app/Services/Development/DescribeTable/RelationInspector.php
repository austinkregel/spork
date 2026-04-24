<?php

declare(strict_types=1);

namespace App\Services\Development\DescribeTable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;

class RelationInspector
{
    /**
     * @return string[]
     */
    public function inspect(Model $model): array
    {
        $methods = get_class_methods($model);
        $relationMethods = [];

        foreach ($methods as $method) {
            if ($this->methodReturnsRelation($model, $method)) {
                $relationMethods[] = $method;
            }
        }

        return $relationMethods;
    }

    private function methodReturnsRelation(Model $model, string $method): bool
    {
        try {
            $reflection = new ReflectionMethod($model, $method);
        } catch (\ReflectionException) {
            return false;
        }

        $type = $reflection->getReturnType();

        if (! $type) {
            return false;
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $innerType) {
                if ($this->typeRepresentsRelation($innerType)) {
                    return true;
                }
            }

            return false;
        }

        return $this->typeRepresentsRelation($type);
    }

    private function typeRepresentsRelation(ReflectionNamedType $type): bool
    {
        $className = $type->getName();

        if (! class_exists($className)) {
            return false;
        }

        $reflection = new ReflectionClass($className);

        if ($reflection->getName() === Relation::class) {
            return true;
        }

        if ($reflection->isSubclassOf(Relation::class)) {
            return true;
        }

        if ($parent = $reflection->getParentClass()) {
            if ($parent->getName() === Relation::class || $parent->isSubclassOf(Relation::class)) {
                return true;
            }
        }

        return false;
    }
}
