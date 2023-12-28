<?php

namespace Tests\Integration\Operations;

use App\Operations\Operation;

class CustomConnectionOperation extends Operation
{
    public $queueConnection = 'custom';

    public function run()
    {
        //
    }
}
