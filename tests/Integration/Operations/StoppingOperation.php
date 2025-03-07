<?php

declare(strict_types=1);

namespace Tests\Integration\Operations;

use App\Exceptions\OperationStoppedException;
use App\Operations\Operation;

class StoppingOperation extends Operation
{
    public function run()
    {
        throw new OperationStoppedException;
    }
}
