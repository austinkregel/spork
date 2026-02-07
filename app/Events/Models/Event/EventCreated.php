<?php

declare(strict_types=1);

namespace App\Events\Models\Event;

use App\Events\AbstractLogicalEvent;
use App\Models\Event;

class EventCreated extends AbstractLogicalEvent
{
    public function __construct(
        public Event $model,
    ) {}
}
