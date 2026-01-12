<?php

declare(strict_types=1);

namespace App\Services\Tags;

use App\Models\Taggable;
use App\Services\Code;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Model;

class TaggableClassDiscoveryService
{
    public const CACHE_KEY = 'spork.tags.taggable_classes.v1';

    public function __construct(
        protected CacheRepository $cache,
    ) {}

    /**
     * @return array<int, class-string<Model>>
     */
    public function discover(bool $useCache = true): array
    {
        if ($useCache) {
            /** @var array<int, class-string<Model>> $cached */
            $cached = $this->cache->rememberForever(self::CACHE_KEY, fn () => $this->discover(false));

            return $cached;
        }

        $classes = Code::instancesOf(Taggable::class)->getClasses();

        $models = array_values(array_filter($classes, function (string $class): bool {
            return class_exists($class) && is_subclass_of($class, Model::class);
        }));

        sort($models);

        /** @var array<int, class-string<Model>> $models */
        return $models;
    }

    public function forgetCache(): void
    {
        $this->cache->forget(self::CACHE_KEY);
    }
}
