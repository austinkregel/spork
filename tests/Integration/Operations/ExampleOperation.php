<?php

declare(strict_types=1);

namespace Tests\Integration\Operations;

use App\Operations\Operation;

class ExampleOperation extends Operation
{
    protected $hasRun = false;

    protected $casts = [
        'hooked_into_queue' => 'bool',
    ];

    public function queue()
    {
        $this->hooked_into_queue = true;
        $this->save();
    }

    public function run()
    {
        $this->hasRun = true;
    }

    public function hasRun(): bool
    {
        return $this->hasRun;
    }

    public function hookedIntoQueue(): bool
    {
        return $this->hooked_into_queue;
    }
}
