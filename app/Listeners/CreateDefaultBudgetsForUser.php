<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Services\Finance\DefaultBudgetService;
use Illuminate\Auth\Events\Registered;

class CreateDefaultBudgetsForUser
{
    public function __construct(
        protected DefaultBudgetService $defaultBudgetService,
    ) {}

    /**
     * Handle the event.
     *
     * @param  object  $event
     */
    public function handle(Registered $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $this->defaultBudgetService->createForUser($user, skipIfAnyBudgetExists: true);
    }
}
