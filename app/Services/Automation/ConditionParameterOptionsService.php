<?php

declare(strict_types=1);

namespace App\Services\Automation;

use App\Services\Code;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ConditionParameterOptionsService
{
    /**
     * @return array<int, array{label:string,options:array<int, array{value:string,name:string}>}>
     */
    public function forAutomatedTagConditions(): array
    {
        $contexts = $this->discoverConditionContextsFromListeners();

        $groups = [];
        foreach ($contexts as $contextKey => $className) {
            $options = $this->buildFieldOptions($contextKey, $className);

            if (empty($options)) {
                continue;
            }

            $groups[] = [
                'label' => Str::headline($contextKey),
                'options' => $options,
            ];
        }

        usort($groups, fn (array $a, array $b) => strnatcasecmp($a['label'], $b['label']));

        return $groups;
    }

    /**
     * @return array<string, class-string|null> key => model class (best effort)
     */
    protected function discoverConditionContextsFromListeners(): array
    {
        $listenerClasses = Code::instancesOf(ShouldQueue::class)->getClasses();
        $listenerClasses = array_values(array_filter(
            $listenerClasses,
            fn (string $class) => str_contains($class, 'ApplyUserAutomatedTagsTo')
        ));

        $classMap = Code::composerMappedClasses();

        $contexts = [];

        foreach ($listenerClasses as $listenerClass) {
            $file = $classMap[$listenerClass] ?? null;
            if (! $file || ! is_string($file) || ! file_exists($file)) {
                continue;
            }

            $contents = file_get_contents($file);
            if ($contents === false) {
                continue;
            }

            $contexts = array_merge($contexts, $this->extractContextsFromListenerContents($contents));
        }

        ksort($contexts);

        return $contexts;
    }

    /**
     * @return array<string, class-string|null>
     */
    protected function extractContextsFromListenerContents(string $contents): array
    {
        $useMap = $this->extractUseMap($contents);

        $processArray = $this->extractProcessContextArray($contents);
        if ($processArray === null) {
            return [];
        }

        $contexts = [];

        preg_match_all(
            "/'(?<key>[a-zA-Z_][a-zA-Z0-9_]*)'\\s*=>\\s*\\$(?<var>[a-zA-Z_][a-zA-Z0-9_]*)(?:->toArray\\(\\))?/m",
            $processArray,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $key = $match['key'] ?? null;
            $var = $match['var'] ?? null;

            if (! is_string($key) || $key === '' || ! is_string($var) || $var === '') {
                continue;
            }

            $classShort = $this->findVarDocblockType($contents, $var);
            $fqcn = $this->resolveClassName($classShort, $useMap);

            $contexts[$key] = $fqcn;
        }

        return $contexts;
    }

    /**
     * @return array<string, string>
     */
    protected function extractUseMap(string $contents): array
    {
        preg_match_all('/^use\\s+([^;]+);/m', $contents, $matches);
        $uses = $matches[1] ?? [];

        $map = [];

        foreach ($uses as $use) {
            $use = trim((string) $use);
            if ($use === '') {
                continue;
            }

            if (str_contains($use, ' as ')) {
                [$fqcn, $alias] = array_map('trim', explode(' as ', $use, 2));
                $map[$alias] = $fqcn;

                continue;
            }

            $map[class_basename($use)] = $use;
        }

        return $map;
    }

    protected function extractProcessContextArray(string $contents): ?string
    {
        $needle = 'process($tag, [';
        $pos = strpos($contents, $needle);
        if ($pos === false) {
            return null;
        }

        $start = $pos + strlen($needle);
        $depth = 1;
        $i = $start;
        $len = strlen($contents);

        while ($i < $len) {
            $ch = $contents[$i];
            if ($ch === '[') {
                $depth++;
            } elseif ($ch === ']') {
                $depth--;
                if ($depth === 0) {
                    return substr($contents, $start, $i - $start);
                }
            }
            $i++;
        }

        return null;
    }

    protected function findVarDocblockType(string $contents, string $varName): ?string
    {
        if (preg_match('/@var\\s+([^\\s]+)\\s+\\$'.preg_quote($varName, '/').'/m', $contents, $m)) {
            return $m[1] ?? null;
        }

        return null;
    }

    /**
     * @param  array<string, string>  $useMap
     * @return class-string|null
     */
    protected function resolveClassName(?string $classShort, array $useMap): ?string
    {
        if (! $classShort || ! is_string($classShort)) {
            return null;
        }

        if (str_contains($classShort, '\\') && class_exists($classShort)) {
            return $classShort;
        }

        $fqcn = $useMap[$classShort] ?? null;
        if (is_string($fqcn) && class_exists($fqcn)) {
            return $fqcn;
        }

        $guess = 'App\\Models\\'.$classShort;
        if (class_exists($guess)) {
            return $guess;
        }

        return null;
    }

    /**
     * @param  class-string|null  $modelClass
     * @return array<int, array{value:string,name:string}>
     */
    protected function buildFieldOptions(string $contextKey, ?string $modelClass): array
    {
        if (! $modelClass || ! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return [];
        }

        /** @var Model $model */
        $model = new $modelClass;

        $fields = array_values(array_unique(array_filter(array_merge(
            ['id'],
            $model->getFillable(),
            array_keys($model->getCasts())
        ))));

        sort($fields);

        return array_values(array_map(
            fn (string $field) => [
                'value' => $contextKey.'.'.$field,
                'name' => Str::headline($field),
            ],
            $fields
        ));
    }
}
