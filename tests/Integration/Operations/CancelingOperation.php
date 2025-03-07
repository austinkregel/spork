<?php

declare(strict_types=1);

namespace Tests\Integration\Operations;

use App\Exceptions\OperationCanceledException;
use App\Operations\Operation;

class CancelingOperation extends Operation
{
    public function run()
    {
        throw new OperationCanceledException;
    }
}
