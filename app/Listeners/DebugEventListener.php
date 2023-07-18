<?php

declare(strict_types=1);

namespace App\Listeners;

class DebugEventListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        info('Event fired: '.get_class($event), [
            'event' => $event,
        ]);
    }
}
