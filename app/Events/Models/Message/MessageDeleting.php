<?php

declare(strict_types=1);

namespace App\Events\Models\Message;

use App\Events\AbstractLogicalEvent;
use App\Models\Message;

class MessageDeleting extends AbstractLogicalEvent
{
    public function __construct(
        public Message $model,
    ) {}
}
