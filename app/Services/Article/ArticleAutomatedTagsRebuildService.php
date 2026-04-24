<?php

declare(strict_types=1);

namespace App\Services\Article;

use App\Models\Article;
use App\Models\ExternalRssFeed;
use App\Models\Tag;
use App\Models\User;
use App\Services\ConditionService;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;

class ArticleAutomatedTagsRebuildService
{
    public function __construct(
        protected LoggerInterface $logger,
    ) {}

    /**
     * Rebuild article taggables for a user by:
     * - deleting existing taggables for article-targeting automatic tags
     * - re-evaluating tag conditions against each article and re-attaching matches
     *
     * @return array{tags:int,articles:int,deleted_taggables:int,attached_taggables:int}
     */
    public function rebuildForUser(User $user): array
    {
        $tags = $user->tags()
            ->with('conditions')
            ->where('type', 'automatic')
            ->whereHas('conditions', fn ($q) => $q->where('parameter', 'like', 'article.%'))
            ->get();

        if ($tags->isEmpty()) {
            return [
                'tags' => 0,
                'articles' => 0,
                'deleted_taggables' => 0,
                'attached_taggables' => 0,
            ];
        }

        $tagIds = $tags->pluck('id')->all();

        $feedIds = ExternalRssFeed::query()
            ->where('owner_type', User::class)
            ->where('owner_id', $user->id)
            ->pluck('id')
            ->all();

        if (empty($feedIds)) {
            return [
                'tags' => $tags->count(),
                'articles' => 0,
                'deleted_taggables' => 0,
                'attached_taggables' => 0,
            ];
        }

        $articleQuery = Article::query()
            ->where('author_type', ExternalRssFeed::class)
            ->whereIn('author_id', $feedIds);

        $articleCount = (clone $articleQuery)->count();

        // Delete existing article taggables for these tags, scoped to the user's articles.
        $deleted = DB::table('taggables')
            ->where('taggable_type', Article::class)
            ->whereIn('tag_id', $tagIds)
            ->whereIn('taggable_id', (clone $articleQuery)->select('id'))
            ->delete();

        $conditionService = new ConditionService($this->logger);
        $attached = 0;

        (clone $articleQuery)
            ->select(['id', 'headline', 'content', 'url', 'created_at', 'updated_at', 'last_modified', 'author_id', 'author_type'])
            ->orderBy('id')
            ->chunkById(200, function ($articles) use ($tags, $conditionService, &$attached): void {
                /** @var Article $article */
                foreach ($articles as $article) {
                    // Re-evaluate all relevant tags against this article.
                    $tagsToApply = $tags->filter(fn (Tag $tag) => $conditionService->process($tag, [
                        // Mirror ApplyUserAutomatedTagsToArticle listener payload shape.
                        'article' => $article->toArray(),
                    ]));

                    if ($tagsToApply->isEmpty()) {
                        continue;
                    }

                    $article->tags()->syncWithoutDetaching($tagsToApply->pluck('id')->all());
                    $attached += $tagsToApply->count();
                }
            });

        return [
            'tags' => $tags->count(),
            'articles' => $articleCount,
            'deleted_taggables' => (int) $deleted,
            'attached_taggables' => (int) $attached,
        ];
    }
}
