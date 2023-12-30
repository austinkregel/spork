<?php

declare(strict_types=1);

namespace Tests\Integration\Operations;

use App\Operations\Operation;

class TaggedOperation extends Operation
{
    public function run()
    {
        //
    }

    public function tags()
    {
        return [
            'custom-tags',
            'foobar',
            'tagged',
        ];
    }
}
