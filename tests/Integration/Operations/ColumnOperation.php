<?php

namespace Tests\Integration\Operations;

use App\Operations\Operation;

class ColumnOperation extends Operation
{
    protected $hasRun = false;

    public function run()
    {
        $this->hasRun = true;
    }

    public function hasRun(): bool
    {
        return $this->hasRun;
    }
}
