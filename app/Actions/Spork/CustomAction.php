<?php

declare(strict_types=1);

namespace App\Actions\Spork;

use Illuminate\Database\Eloquent\Model;

/**
 * Actions are implemented as invokable controllers.
 * They should expect to be sent an array of ids that represent the main model.
 * public function __invoke(Dispatcher $dispatcher, Request $request)
 */
abstract class CustomAction
{
    public function __construct(
        public string $name = 'Set Namecheap DNS',
        public string $slug = 'custom-action',
        public ?string $models = null,
    ) {
    }
}
