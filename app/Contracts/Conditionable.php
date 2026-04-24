<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property Collection $conditions
 * @property bool $must_all_conditions_pass
 */
interface Conditionable
{
    public function conditions(): MorphMany;
}
