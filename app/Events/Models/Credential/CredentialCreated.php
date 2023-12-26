<?php

declare(strict_types=1);

namespace App\Events\Models\Credential;

use App\Events\AbstractLogicalEvent;
use App\Models\Credential;

class CredentialCreated extends AbstractLogicalEvent
{
    public function __construct(
        public Credential $model,
    ) {
    }
}
