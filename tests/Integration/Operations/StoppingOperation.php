<?php

namespace Tests\Integration\Operations;

use App\Operations\Operation;
use App\Exceptions\OperationStoppedException;

class StoppingOperation extends Operation
{
    public function run()
    {
        throw new OperationStoppedException();
    }
}
