<?php

declare(strict_types=1);

namespace App\Models;

use App\Events\Models\Membership\MembershipCreated;
use App\Events\Models\Membership\MembershipCreating;
use App\Events\Models\Membership\MembershipDeleted;
use App\Events\Models\Membership\MembershipDeleting;
use App\Events\Models\Membership\MembershipUpdated;
use App\Events\Models\Membership\MembershipUpdating;
use Laravel\Jetstream\Membership as JetstreamMembership;

class Membership extends JetstreamMembership
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    public $dispatchesEvents = [
        'created' => MembershipCreated::class,
        'creating' => MembershipCreating::class,
        'deleting' => MembershipDeleting::class,
        'deleted' => MembershipDeleted::class,
        'updating' => MembershipUpdating::class,
        'updated' => MembershipUpdated::class,
    ];
}
