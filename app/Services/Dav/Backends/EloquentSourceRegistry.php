<?php

declare(strict_types=1);

namespace App\Services\Dav\Backends;

use App\Contracts\Dav\DavCollectionSource;
use App\Services\Code;
use Illuminate\Support\Collection;

/**
 * Discovers DavCollectionSource implementations through the project's Code
 * reflection helper. Sources are cached for the duration of the request.
 */
class EloquentSourceRegistry
{
    /** @var array<string, DavCollectionSource> */
    private array $sources = [];

    private bool $discovered = false;

    public function register(DavCollectionSource $source): void
    {
        $this->sources[$source::class] = $source;
    }

    /** @return Collection<int, DavCollectionSource> */
    public function all(): Collection
    {
        $this->discover();

        return collect(array_values($this->sources));
    }

    /** @return Collection<int, DavCollectionSource> */
    public function ofType(string $type): Collection
    {
        return $this->all()->filter(fn (DavCollectionSource $source) => $source->collectionType() === $type)
            ->values();
    }

    public function discover(): void
    {
        if ($this->discovered) {
            return;
        }

        $this->discovered = true;

        foreach (Code::instancesOf(DavCollectionSource::class)->getClasses() as $class) {
            if (! is_subclass_of($class, DavCollectionSource::class) && $class !== DavCollectionSource::class) {
                continue;
            }

            try {
                $reflection = new \ReflectionClass($class);
            } catch (\ReflectionException) {
                continue;
            }

            if ($reflection->isAbstract() || $reflection->isInterface()) {
                continue;
            }

            if (isset($this->sources[$class])) {
                continue;
            }

            $this->sources[$class] = app($class);
        }
    }
}
