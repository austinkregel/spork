<?php

declare(strict_types=1);

namespace Tests\Integration\Operations;

use App\Operations\Operation;

class CustomQueueOperation extends Operation
{
    public $queue = 'custom';

    public function run()
    {
        //
    }
}
