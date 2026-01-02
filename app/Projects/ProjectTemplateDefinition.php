<?php

declare(strict_types=1);

namespace App\Projects;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @phpstan-type ProjectTemplateSection array{key: string, label: string, description?: string, allowed_resource_groups?: list<string>}
 */
class ProjectTemplateDefinition implements Arrayable
{
    /**
     * @param  list<ProjectTemplateSection>  $sections
     * @param  list<class-string>  $preferred_resource_types
     */
    public function __construct(
        public readonly ProjectTemplate $template,
        public readonly string $label,
        public readonly string $description,
        public readonly array $sections,
        public readonly array $preferred_resource_types = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'key' => $this->template->value,
            'label' => $this->label,
            'description' => $this->description,
            'sections' => $this->sections,
            'preferred_resource_types' => $this->preferred_resource_types,
        ];
    }
}


