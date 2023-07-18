<?php

namespace App\Contracts\Services\News;

interface NewsServiceContract
{
    public function query(string $query): array;

    public function headlines(string $query, ?string $category = null): array;
}
