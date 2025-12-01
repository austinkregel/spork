<?php

declare(strict_types=1);

namespace Tests\Fixtures\Operations;

use App\Operations\Operation;

class TestEchoOperation extends Operation
{
    protected $table = 'test_operations';

    protected $fillable = [
        'payload',
        'should_run_at',
        'started_run_at',
        'finished_run_at',
    ];

    public static array $payloads = [];

    public function run(): void
    {
        static::$payloads[] = $this->payload;
        $this->output = $this->payload;
        $this->save();
    }
}


