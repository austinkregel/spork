<?php

namespace App\Features;

use Illuminate\Support\Lottery;

class LinkShortening
{
    /**
     * Resolve the feature's initial value.
     */
    public function resolve(mixed $scope): mixed
    {
        return false;
    }
}
