<?php

declare(strict_types=1);

namespace App\Navigation;

final readonly class PillarSubNavItem
{
    /**
     * @param  array<int, PillarSubNavItem>  $children
     */
    public function __construct(
        public string $label,
        public string $href,
        public string $icon,
        public ?string $badge_count = null,
        public bool $active = false,
        public array $children = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'href' => $this->href,
            'icon' => $this->icon,
            'badge_count' => $this->badge_count,
            'active' => $this->active,
            'children' => array_map(fn (PillarSubNavItem $c) => $c->toArray(), $this->children),
        ];
    }
}
