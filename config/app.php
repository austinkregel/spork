<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Facade;

return [

    'aliases' => Facade::defaultAliases()->merge([
        // 'Example' => App\Facades\Example::class,
        'Operator' => App\Operations\Operator::class,
    ])->toArray(),

];
