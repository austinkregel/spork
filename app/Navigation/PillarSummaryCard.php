<?php

declare(strict_types=1);

namespace App\Navigation;

final readonly class PillarSummaryCard
{
    public function __construct(
        public string $title,
        public string|int|float $value,
        public ?string $delta = null,
        public ?string $delta_direction = null,
        public string $href = '#',
        public string $icon = 'ChartBarIcon',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'value' => $this->value,
            'delta' => $this->delta,
            'delta_direction' => $this->delta_direction,
            'href' => $this->href,
            'icon' => $this->icon,
        ];
    }
}
