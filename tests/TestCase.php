<?php

declare(strict_types=1);

namespace Tests;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Queue;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function setUp() : void
    {
        parent::setUp();

        Carbon::setTestNow('2019-07-04 12:00:00');
        Queue::fake();
    }
}
