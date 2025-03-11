<?php

declare(strict_types=1);

namespace App\Listeners\Article;

use App\Events\Models\Article\ArticleCreated;
use App\Models\Article;
use App\Models\ExternalRssFeed;
use App\Models\Tag;
use App\Models\User;
use App\Services\ConditionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;

class ApplyUserAutomatedTagsToArticle implements ShouldQueue
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
    public function handle(ArticleCreated $event): void
    {
        /** @var Article $article */
        $article = $event->model;
        /** @var ExternalRssFeed $feed */
        $feed = $article->externalRssFeed;

        /** @var User $user */
        $user = $feed->owner;

        if (empty($user)) {
            return;
        }

        $tags = $user->tags()->with('conditions')->where('type', 'automatic')->get();

        $conditionService = new ConditionService($this->logger);

        $tagsToApply = $tags->filter(fn (Tag $tag) => $conditionService->process($tag, [
            'article' => $article,
        ]));

        foreach ($tagsToApply as $tag) {
            if (! $article->tags()->where('id', $tag->id)->exists()) {
                $article->tags()->attach($tag);
            }
        }
    }
}
