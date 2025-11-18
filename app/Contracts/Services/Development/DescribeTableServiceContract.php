<?php

declare(strict_types=1);

namespace App\Contracts\Services\Development;

use Illuminate\Database\Eloquent\Model;

interface DescribeTableServiceContract
{
    public function describe(Model $model): array;

    public function describeTable(string $table): array;
}

