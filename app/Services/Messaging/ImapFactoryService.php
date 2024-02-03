<?php
declare(strict_types=1);

namespace App\Services\Messaging;

use App\Contracts\Services\ImapServiceContract;
use App\Models\Credential;

class ImapFactoryService
{
    public function make(Credential $credential): ImapServiceContract
    {
        return new ImapCredentialService($credential);
    }
}
