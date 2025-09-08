<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Models\Article\ArticleCreated;
use App\Events\Models\Email\EmailCreated;
use App\Events\Models\Message\MessageCreated;
use App\Events\Models\Transaction\TransactionCreated;
use App\Models\Article;
use App\Models\Email;
use App\Models\ExternalRssFeed;
use App\Models\Finance\Transaction;
use App\Models\Message;
use App\Models\Tag;
use App\Models\User;
use App\Services\ConditionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

class ApplyUserAutomatedTags implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected LoggerInterface $logger,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(
        ArticleCreated | MessageCreated | EmailCreated | TransactionCreated $event
    ): void {
        /** @var Model $model */
        $model = $event->model;
        if (empty($model)) {
            $this->logger->error("Model not found.");
            return ;
        }

        // Try to find the user (owner) for the model
        $user = $this->resolveUser($model);
        if (empty($user)) {
            $this->logger->error('Could not resolve user for this model.');
            return ;
        }

        $tags = $user->tags()->with('conditions')->where('type', 'automatic')->get();
        $this->logger->notice('Found ' . $tags->count() . ' automatic tags for user ' . $user->id, [get_class($this)]);
        $conditionService = new ConditionService($this->logger);

        $context = $this->buildContext($model);
        $tagsToApply = $tags->filter(fn (Tag $tag) => $conditionService->process($tag, $context));

        $applied = 0;
        foreach ($tagsToApply as $tag) {
            if (!$model->tags()->where('id', $tag->id)->exists()) {
                $model->tags()->attach($tag);
                $applied++;
            }
        }

        $this->logger->info("Applied $applied automatic tags.");
    }

    protected function resolveUser(Model $model):? User
    {
        return match (get_class($model)) {
            Article::class => $model->externalRssFeed->owner ?? null,
            Email::class => $model->user ?? $model->credential->user ?? null,
            Message::class => $model->credential->user ?? null,
            Transaction::class => $model->account->credential->user ?? null,
        };
    }

    protected function buildContext(Model $model): array
    {
        // Build context based on model type
        $class = get_class($model);
        return match ($class) {
            Article::class => ['article' => $model->toArray()],
            Email::class => ['email' => $model],
            Message::class => ['message' => $model],
            Transaction::class => [
                'transaction' => $model,
                'account' => $model->account ?? null,
            ],
            default => [Str::snake(class_basename($class),'_') => $model],
        };
    }
}
