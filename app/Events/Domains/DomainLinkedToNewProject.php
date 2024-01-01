<?php

declare(strict_types=1);

namespace App\Events\Domains;

use App\Contracts\LogicalEvent;
use App\Events\AbstractLogicalEvent;
use Illuminate\Database\Eloquent\Model;

class DomainLinkedToNewProject extends AbstractLogicalEvent implements LogicalEvent
{
    public function __construct(Model $model)
    {
        //
    }
}
