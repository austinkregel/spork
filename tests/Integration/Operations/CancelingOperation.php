<?php

namespace Tests\Integration\Operations;

use App\Operations\Operation;
use App\Exceptions\OperationCanceledException;

class CancelingOperation extends Operation
{
    public function run()
    {
        throw new OperationCanceledException();
    }
}
