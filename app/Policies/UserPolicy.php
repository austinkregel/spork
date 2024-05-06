<?php

declare(strict_types=1);

namespace App\Policies;

class UserPolicy extends AbstractPolicy
{
    public const MODEL_PERMISSION_NAME = 'credential';
}
