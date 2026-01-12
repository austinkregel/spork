<?php

declare(strict_types=1);

namespace App\Services\Projects;

use App\Services\Code;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ProjectResourceRegistry
{
    public function __construct(
        private readonly CacheRepository $cache,
    ) {}

    /**
     * @return array<class-string, array{label?: string, group?: string}>
     */
    public function resources(): array
    {
        /** @var array<class-string, array{label?: string, group?: string}> $resources */
        $resources = config('projects.resources', []);

        if (! (bool) config('projects.dynamic.enabled', false)) {
            return $resources;
        }

        /** @var array<class-string, array{label?: string, group?: string}> $dynamic */
        $dynamic = $this->cache->remember(
            key: 'projects:resource-registry:dynamic',
            ttl: (int) config('projects.dynamic.cache_ttl_seconds', 86400),
            callback: fn () => $this->buildDynamicResources(),
        );

        // Curated config should win if there's overlap.
        return array_merge($dynamic, $resources);
    }

    /**
     * @return array<class-string, array{label?: string, group?: string}>
     */
    private function buildDynamicResources(): array
    {
        /** @var list<class-string> $interfaces */
        $interfaces = config('projects.dynamic.interfaces', []);
        /** @var list<class-string> $deny */
        $deny = config('projects.dynamic.deny', []);

        $resources = [];

        foreach ($interfaces as $interface) {
            $classes = Code::instancesOf($interface)->getClasses();

            foreach ($classes as $fqcn) {
                if (! is_string($fqcn) || $fqcn === '') {
                    continue;
                }

                if (in_array($fqcn, $deny, true)) {
                    continue;
                }

                if (! str_starts_with($fqcn, 'App\\Models\\')) {
                    continue;
                }

                if (! class_exists($fqcn) || ! is_subclass_of($fqcn, Model::class)) {
                    continue;
                }

                $resources[$fqcn] = [
                    'label' => Str::title(Str::snake(class_basename($fqcn), ' ')),
                    'group' => $this->guessGroupFromClass($fqcn),
                ];
            }
        }

        ksort($resources);

        return $resources;
    }

    private function guessGroupFromClass(string $fqcn): string
    {
        if (str_contains($fqcn, '\\Finance\\')) {
            return 'finance';
        }

        if (str_contains($fqcn, '\\Infrastructure\\')) {
            return 'infrastructure';
        }

        if (str_contains($fqcn, '\\Article\\') || str_contains($fqcn, 'Rss')) {
            return 'rss';
        }

        return 'other';
    }

    /**
     * Clear cached dynamic resources (useful after deploys / composer updates).
     */
    public function forgetCache(): void
    {
        $this->cache->forget('projects:resource-registry:dynamic');
    }

    /**
     * @return array<string, string> group => label
     */
    public function groups(): array
    {
        /** @var array<string, string> $groups */
        $groups = config('projects.groups', []);

        return $groups;
    }

    /**
     * @return list<class-string>
     */
    public function allowedResourceTypes(): array
    {
        return array_values(array_keys($this->resources()));
    }

    public function isAllowed(string $resource_type): bool
    {
        $resource_type = $this->normalizeResourceType($resource_type);

        return array_key_exists($resource_type, $this->resources());
    }

    /**
     * @return array{type: class-string, label: string, group: string}
     */
    public function metadataFor(string $resource_type): array
    {
        /** @var class-string $resource_type */
        $resource_type = $this->normalizeResourceType($resource_type);

        $meta = $this->resources()[$resource_type] ?? [];

        $group = (string) Arr::get($meta, 'group', 'other');
        $label = (string) Arr::get($meta, 'label', Str::title(Str::snake(class_basename($resource_type), ' ')));

        return [
            'type' => $resource_type,
            'label' => $label,
            'group' => $group,
        ];
    }

    /**
     * @return array{groups: array<string,string>, resources: list<array{type: class-string, label: string, group: string}>}
     */
    public function forFrontend(): array
    {
        $resources = collect($this->allowedResourceTypes())
            ->map(fn (string $type) => $this->metadataFor($type))
            ->sortBy(['group', 'label'])
            ->values()
            ->all();

        return [
            'groups' => $this->groups(),
            'resources' => $resources,
        ];
    }

    /**
     * Normalize a resource type coming from HTTP.
     *
     * Some clients will send `App\\\\Models\\\\Server` instead of `App\\Models\\Server`.
     */
    private function normalizeResourceType(string $resource_type): string
    {
        return str_replace('\\\\', '\\', trim($resource_type));
    }
}
