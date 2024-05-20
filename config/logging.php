<?php

declare(strict_types=1);

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    'channels' => [
        'flare' => [
            'driver' => 'flare',
        ],

        'bugsnag' => [
            'driver' => 'bugsnag',
        ],
    ],

];
