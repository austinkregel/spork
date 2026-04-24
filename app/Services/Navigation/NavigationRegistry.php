<?php

declare(strict_types=1);

namespace App\Services\Navigation;

use App\Contracts\Navigation\Pillars\PillarSubNavProviderContract;
use App\Contracts\Services\Navigation\NavigationRegistryContract;
use App\Models\User;
use App\Navigation\Pillar;
use App\Navigation\PillarSubNavItem;
use App\Services\Programming\LaravelProgrammingStyle;
use Illuminate\Support\Facades\Route;

final class NavigationRegistry implements NavigationRegistryContract
{
    /** @var list<PillarSubNavProviderContract>|null */
    private ?array $providers = null;

    public function __construct(
        private readonly CrudPillarNavigationCollector $crud_pillar_navigation,
    ) {}

    public function providers(): array
    {
        if ($this->providers !== null) {
            return $this->providers;
        }

        $cached = $this->loadCachedProviderClasses();
        if ($cached !== null) {
            $this->providers = $this->instantiateSorted($cached);

            return $this->providers;
        }

        $discovered = LaravelProgrammingStyle::instancesOf(PillarSubNavProviderContract::class)
            ->getClasses();

        $this->providers = $this->instantiateSorted($discovered);

        return $this->providers;
    }

    /**
     * @param  list<class-string<PillarSubNavProviderContract>>  $classes
     * @return list<PillarSubNavProviderContract>
     */
    private function instantiateSorted(array $classes): array
    {
        $instances = [];
        foreach ($classes as $class) {
            /** @var class-string<PillarSubNavProviderContract> $class */
            $instances[] = app($class);
        }

        usort($instances, fn (PillarSubNavProviderContract $a, PillarSubNavProviderContract $b) => $a::pillar()->name <=> $b::pillar()->name);

        return $instances;
    }

    /**
     * @return list<class-string<PillarSubNavProviderContract>>|null
     */
    private function loadCachedProviderClasses(): ?array
    {
        $path = base_path('bootstrap/cache/navigation.php');
        if (! is_file($path)) {
            return null;
        }

        /** @var mixed $data */
        $data = require $path;
        if (! is_array($data) || ! isset($data['providers']) || ! is_array($data['providers'])) {
            return null;
        }

        /** @var list<class-string<PillarSubNavProviderContract>> $providers */
        $providers = [];
        foreach ($data['providers'] as $class) {
            if (is_string($class) && class_exists($class)
                && is_subclass_of($class, PillarSubNavProviderContract::class)) {
                $providers[] = $class;
            }
        }

        return $providers === [] ? null : $providers;
    }

    public function providerFor(Pillar $pillar): ?PillarSubNavProviderContract
    {
        foreach ($this->providers() as $provider) {
            if ($provider::pillar() === $pillar) {
                return $provider;
            }
        }

        return null;
    }

    public function pillarsPayload(User $user): array
    {
        $out = [];
        foreach ($this->providers() as $provider) {
            $p = $provider::pillar();
            $out[] = [
                'slug' => $p->value,
                'label' => $p->label(),
                'icon' => $p->icon(),
                'href' => route($p->value.'.landing'),
            ];
        }

        return $out;
    }

    public function subNavForPillar(Pillar $pillar, User $user): array
    {
        $provider = $this->providerFor($pillar);
        $items = $provider === null ? [] : array_map(fn ($item) => $item->toArray(), $provider->subNav($user));

        $manage_children = $this->crud_pillar_navigation->manageItemsForPillar($pillar);
        if ($manage_children === []) {
            return $items;
        }

        $manage_href = Route::has('manage.index') ? route('manage.index') : '/-/manage';

        $items[] = (new PillarSubNavItem(
            __('nav.manage_crud_tables'),
            $manage_href,
            'Cog6ToothIcon',
            null,
            false,
            $manage_children,
        ))->toArray();

        return $items;
    }

    public function summaryCardsForPillar(Pillar $pillar, User $user): array
    {
        $provider = $this->providerFor($pillar);
        if ($provider === null) {
            return [];
        }

        return array_map(fn ($c) => $c->toArray(), $provider->summaryCards($user));
    }

    public function currentPillar(?string $path): ?Pillar
    {
        if ($path === null || $path === '') {
            return null;
        }

        $path = trim($path, '/');
        $parts = explode('/', $path);
        if (count($parts) < 2 || $parts[0] !== '-') {
            return null;
        }

        // Direct pillar segment, e.g. `/-/automations/...` or `/-/finance/...`.
        $direct = Pillar::tryFrom($parts[1]);
        if ($direct !== null) {
            return $direct;
        }

        // Manage CRUD pages (`/-/manage/{slug}`) should keep the owning pillar
        // highlighted so users don't lose the context they navigated from.
        if ($parts[1] === 'manage' && isset($parts[2]) && $parts[2] !== '') {
            return $this->crud_pillar_navigation->pillarForSlug($parts[2]);
        }

        return null;
    }
}
