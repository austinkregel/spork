<?php

declare(strict_types=1);

namespace App\Services\Messaging;

use App\Contracts\Services\ImapServiceContract;
use App\Contracts\Services\Messaging\ImapFactoryServiceContract;
use App\Models\Credential;

class ImapFactoryService implements ImapFactoryServiceContract
{
    public function make(Credential $credential): ImapServiceContract
    {
        return new ImapCredentialService($credential);
    }
}
