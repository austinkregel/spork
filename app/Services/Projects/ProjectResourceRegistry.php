<?php

declare(strict_types=1);

namespace App\Services\Projects;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ProjectResourceRegistry
{
    /**
     * @return array<class-string, array{label?: string, group?: string}>
     */
    public function resources(): array
    {
        /** @var array<class-string, array{label?: string, group?: string}> $resources */
        $resources = config('projects.resources', []);

        return $resources;
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


