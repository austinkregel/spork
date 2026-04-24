<?php

declare(strict_types=1);

namespace App\Services\Navigation;

use App\Models\Crud;
use App\Navigation\Pillar;
use App\Navigation\PillarSubNavItem;
use App\Services\Programming\LaravelProgrammingStyle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Maps {@see Crud} models that opt in via {@see Model::pillar()} into per-pillar "Manage" links.
 * Built at application boot — not during HTTP handling of arbitrary requests.
 */
final class CrudPillarNavigationCollector
{
    /**
     * @param  array<string, list<PillarSubNavItem>>  $items_by_pillar_value
     * @param  array<string, string>  $pillar_value_by_slug
     */
    public function __construct(
        private array $items_by_pillar_value,
        private array $pillar_value_by_slug = [],
    ) {}

    public static function discover(): self
    {
        $items_by_pillar = [];
        $pillar_value_by_slug = [];

        $classes = LaravelProgrammingStyle::instancesOf(Crud::class)->getClasses();

        foreach ($classes as $class) {
            if (! is_string($class) || ! class_exists($class)) {
                continue;
            }
            if (! is_subclass_of($class, Model::class)) {
                continue;
            }
            if (! method_exists($class, 'pillar')) {
                continue;
            }

            /** @var class-string<Model> $class */
            $pillar = $class::pillar();
            if (! $pillar instanceof Pillar) {
                continue;
            }

            /** @var Model $model */
            $model = new $class;
            $slug = Str::slug($model->getTable());
            $label = Str::headline(str_replace('_', ' ', $model->getTable()));

            $href = Route::has('manage.show')
                ? route('manage.show', ['slug' => $slug])
                : '/-/manage/'.$slug;

            $items_by_pillar[$pillar->value][] = new PillarSubNavItem(
                $label,
                $href,
                'RectangleStackIcon',
            );

            $pillar_value_by_slug[$slug] = $pillar->value;
        }

        foreach ($items_by_pillar as $key => $items) {
            usort($items, fn (PillarSubNavItem $a, PillarSubNavItem $b) => $a->label <=> $b->label);
            $items_by_pillar[$key] = array_values($items);
        }

        return new self($items_by_pillar, $pillar_value_by_slug);
    }

    /**
     * @return list<PillarSubNavItem>
     */
    public function manageItemsForPillar(Pillar $pillar): array
    {
        return $this->items_by_pillar_value[$pillar->value] ?? [];
    }

    public function pillarForSlug(string $slug): ?Pillar
    {
        $value = $this->pillar_value_by_slug[$slug] ?? null;

        return $value === null ? null : Pillar::tryFrom($value);
    }
}
