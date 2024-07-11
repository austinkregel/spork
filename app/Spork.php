<?php
declare(strict_types=1);

namespace App;

class Spork
{
    protected array $navigation = [];

    public function addNavigation(string $name, string $route, string $icon, array $options = []): array
    {
        return $this->navigation[] = array_merge([
            'name' => $name,
            'icon' => $icon,
            'route' => url($route),
        ], $options);
    }
}
