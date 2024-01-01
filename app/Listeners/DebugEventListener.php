<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Contracts\LogicalListener;

class DebugEventListener implements LogicalListener
{
    public function __construct(
        protected $union = null
    ) {
        //
    }

    public function handle(object $event): void
    {
        info('Event fired: '.get_class($event), [
            'event' => $event,
        ]);
    }
}
