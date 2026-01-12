<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Article;
use App\Models\Credential;
use App\Models\Email;
use App\Models\ExternalRssFeed;
use App\Models\Finance\PrivacyTransaction;
use App\Models\Finance\Transaction;
use App\Models\Tag;
use App\Models\User;
use App\Services\ConditionService;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;

class AutomatedTagsRebuildService
{
    public function __construct(
        protected LoggerInterface $logger,
        protected PrivacyTransactionTagger $privacyTransactionTagger,
    ) {}

    /**
     * Re-applies the user's automatic tags against their existing taggable models.
     */
    public function rebuildForUser(User $user): void
    {
        $tags = $user->tags()->with('conditions')->where('type', 'automatic')->get();

        if ($tags->isEmpty()) {
            return;
        }

        $conditionService = new ConditionService($this->logger);

        $this->rebuildTransactionTags($user, $tags, $conditionService);
        $this->rebuildPrivacyTransactionTags($user, $tags, $conditionService);
        $this->rebuildEmailTags($user, $tags, $conditionService);
        $this->rebuildArticleTags($user, $tags, $conditionService);
    }

    /**
     * @param  Collection<int, Tag>  $tags
     */
    protected function rebuildTransactionTags(User $user, Collection $tags, ConditionService $conditionService): void
    {
        $accountIds = $user->accounts()->pluck('account_id')->all();

        if (empty($accountIds)) {
            return;
        }

        Transaction::query()
            ->whereIn('account_id', $accountIds)
            ->with('account')
            ->orderBy('id')
            ->chunkById(200, function ($transactions) use ($tags, $conditionService) {
                foreach ($transactions as $transaction) {
                    $account = $transaction->account;
                    if (! $account) {
                        continue;
                    }

                    $toApply = $tags
                        ->filter(fn (Tag $tag) => $conditionService->process($tag, [
                            'transaction' => $transaction,
                            'account' => $account,
                        ]))
                        ->pluck('id')
                        ->all();

                    if (! empty($toApply)) {
                        $transaction->tags()->syncWithoutDetaching($toApply);
                    }
                }
            }, 'id');
    }

    /**
     * @param  Collection<int, Tag>  $tags
     */
    protected function rebuildPrivacyTransactionTags(User $user, Collection $tags, ConditionService $conditionService): void
    {
        $privacyCredentialIds = Credential::query()
            ->where('user_id', $user->id)
            ->where('type', Credential::TYPE_FINANCE)
            ->where('service', Credential::PRIVACY)
            ->pluck('id')
            ->all();

        if (empty($privacyCredentialIds)) {
            return;
        }

        PrivacyTransaction::query()
            ->whereIn('credential_id', $privacyCredentialIds)
            ->orderBy('id')
            ->chunkById(200, function ($privacyTransactions) use ($tags, $conditionService) {
                foreach ($privacyTransactions as $privacyTransaction) {
                    $payload = $this->privacyTransactionTagger->buildConditionPayload($privacyTransaction);

                    $toApply = $tags
                        ->filter(fn (Tag $tag) => $conditionService->process($tag, $payload))
                        ->pluck('id')
                        ->all();

                    if (! empty($toApply)) {
                        $privacyTransaction->tags()->syncWithoutDetaching($toApply);
                    }
                }
            }, 'id');
    }

    /**
     * @param  Collection<int, Tag>  $tags
     */
    protected function rebuildEmailTags(User $user, Collection $tags, ConditionService $conditionService): void
    {
        $credentialIds = Credential::query()
            ->where('user_id', $user->id)
            ->pluck('id')
            ->all();

        if (empty($credentialIds)) {
            return;
        }

        Email::query()
            ->whereIn('credential_id', $credentialIds)
            ->with('credential')
            ->orderBy('id')
            ->chunkById(200, function ($emails) use ($tags, $conditionService) {
                foreach ($emails as $email) {
                    $toApply = $tags
                        ->filter(fn (Tag $tag) => $conditionService->process($tag, [
                            'email' => $email,
                        ]))
                        ->pluck('id')
                        ->all();

                    if (! empty($toApply)) {
                        $email->tags()->syncWithoutDetaching($toApply);
                    }
                }
            }, 'id');
    }

    /**
     * @param  Collection<int, Tag>  $tags
     */
    protected function rebuildArticleTags(User $user, Collection $tags, ConditionService $conditionService): void
    {
        $feedIds = ExternalRssFeed::query()
            ->where('owner_type', User::class)
            ->where('owner_id', $user->id)
            ->pluck('id')
            ->all();

        if (empty($feedIds)) {
            return;
        }

        Article::query()
            ->where('author_type', ExternalRssFeed::class)
            ->whereIn('author_id', $feedIds)
            ->orderBy('id')
            ->chunkById(200, function ($articles) use ($tags, $conditionService) {
                foreach ($articles as $article) {
                    $toApply = $tags
                        ->filter(fn (Tag $tag) => $conditionService->process($tag, [
                            'article' => $article->toArray(),
                        ]))
                        ->pluck('id')
                        ->all();

                    if (! empty($toApply)) {
                        $article->tags()->syncWithoutDetaching($toApply);
                    }
                }
            }, 'id');
    }
}
