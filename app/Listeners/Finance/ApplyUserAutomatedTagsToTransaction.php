<?php

declare(strict_types=1);

namespace App\Listeners\Finance;

use App\Events\Models\Transaction\TransactionCreated;
use App\Models\Finance\Account;
use App\Models\Finance\Transaction;
use App\Models\Tag;
use App\Services\ConditionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;

class ApplyUserAutomatedTagsToTransaction implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected LoggerInterface $logger,
    )
    {
    }

    /**
     * Handle the event.
     */
    public function handle(TransactionCreated $event): void
    {
        $event->model->load('account.credential.user');
        $event->model->refresh();

        /** @var Transaction $transaction */
        $transaction = $event->model;
        /** @var Account $account */
        $account = $transaction->account;
        $credential = $account->credential;

        $user = $credential->user;

        if (empty($user)) {
            return;
        }

        $tags = $user->tags()->with('conditions')->where('type', 'automatic')->get();

        $conditionService = new ConditionService($this->logger);

        $tagsToApply = $tags->filter(fn (Tag $tag) => $conditionService->process($tag, [
            'transaction' => $transaction,
            'account' => $account,
        ]));

        foreach ($tagsToApply as $tag) {
            if (! $transaction->tags()->where('id', $tag->id)->exists()) {
                $transaction->tags()->attach($tag);
            }
        }
    }
}
