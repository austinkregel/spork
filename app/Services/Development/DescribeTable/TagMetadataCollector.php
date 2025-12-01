<?php

declare(strict_types=1);

namespace App\Services\Development\DescribeTable;

use App\Models\Tag;
use App\Models\Taggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TagMetadataCollector
{
    public function __construct(
        private readonly ?\Closure $tagQueryResolver = null,
    ) {
    }

    public function collect(Model $model): Collection
    {
        if (! $model instanceof Taggable) {
            return collect();
        }

        $type = Str::singular($model->getTable());

        return $this->resolveQuery()
            ->whereNull('type')
            ->orWhere('type', $type)
            ->get();
    }

    private function resolveQuery(): Builder
    {
        if ($this->tagQueryResolver instanceof \Closure) {
            return ($this->tagQueryResolver)();
        }

        return Tag::query();
    }
}

