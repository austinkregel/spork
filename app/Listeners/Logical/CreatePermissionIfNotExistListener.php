<?php

declare(strict_types=1);

namespace App\Listeners\Logical;

use App\Contracts\LogicalListener;

class CreatePermissionIfNotExistListener implements LogicalListener
{
    public function __construct(
        protected $union = null
    ) {
        //
    }

    public function handle(object $event): void
    {
        info('Create permission requested if not exist: '.get_class($event), [
            'event' => $event,
        ]);
    }
}
