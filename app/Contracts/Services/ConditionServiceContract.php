<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Contracts\Conditionable;

interface ConditionServiceContract
{
    public function process(Conditionable $item, array $additionalValueData = []): bool;
}
