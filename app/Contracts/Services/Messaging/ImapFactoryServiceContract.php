<?php

declare(strict_types=1);

namespace App\Contracts\Services\Messaging;

use App\Contracts\Services\ImapServiceContract;
use App\Models\Credential;

interface ImapFactoryServiceContract
{
    public function make(Credential $credential): ImapServiceContract;
}

