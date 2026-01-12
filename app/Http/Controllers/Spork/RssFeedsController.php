<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Models\Article;
use App\Models\Article\SocialFeed;
use App\Models\User;
use App\Services\Article\SocialFeedService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RssFeedsController
{
    public function index(Request $request): Response
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user !== null, 404);

        $paginator = Article::query()
            ->latest('created_at')
            ->with('author.tags')
            ->paginate(perPage: (int) $request->integer('per_page', 25));

        return Inertia::render('RssFeeds/Index', [
            'social_feeds' => SocialFeed::query()
                ->visibleTo($user)
                ->with(['tags', 'conditions'])
                ->orderBy('name')
                ->get(),
            'available_tags' => $user->tags()
                ->with('conditions')
                ->whereHas('conditions', fn ($q) => $q->where('parameter', 'like', 'article.%'))
                ->orderBy('type')
                ->orderBy('order_column')
                ->get(),
            'parameter_groups' => $this->articleParameterGroups(),
            'feeds' => $paginator->items(),
            'pagination' => $paginator,
        ]);
    }

    public function show(Request $request, SocialFeed $socialFeed, SocialFeedService $socialFeedService): Response
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user !== null, 404);

        abort_unless($socialFeed->is_public || $socialFeed->user_id === $user->id, 403);

        $socialFeed->load(['tags', 'conditions', 'user']);

        $paginator = $socialFeedService
            ->getArticlesForSocialFeed($socialFeed, [
                'sort' => $request->string('sort', 'published_at_desc')->toString(),
            ])
            ->paginate(perPage: (int) $request->integer('per_page', 25));

        return Inertia::render('RssFeeds/Show', [
            'social_feeds' => SocialFeed::query()
                ->visibleTo($user)
                ->with(['tags', 'conditions'])
                ->orderBy('name')
                ->get(),
            'social_feed' => $socialFeed,
            'available_tags' => $user->tags()
                ->with('conditions')
                ->whereHas('conditions', fn ($q) => $q->where('parameter', 'like', 'article.%'))
                ->orderBy('type')
                ->orderBy('order_column')
                ->get(),
            'parameter_groups' => $this->articleParameterGroups(),
            'feeds' => $paginator->items(),
            'pagination' => $paginator,
        ]);
    }

    public function makePublic(Request $request, SocialFeed $socialFeed): RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user !== null, 404);

        abort_unless($socialFeed->user_id === $user->id, 403);

        $socialFeed->update(['is_public' => true]);

        return back();
    }

    public function makePrivate(Request $request, SocialFeed $socialFeed): RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_unless($user !== null, 404);

        abort_unless($socialFeed->user_id === $user->id, 403);

        $socialFeed->update(['is_public' => false]);

        return back();
    }

    /**
     * @return array<int, array{label:string,options:array<int,array{value:string,name:string}>}>
     */
    protected function articleParameterGroups(): array
    {
        return [
            [
                'label' => 'Article',
                'options' => [
                    ['value' => 'article.headline', 'name' => 'Headline'],
                    ['value' => 'article.content', 'name' => 'Content'],
                    ['value' => 'article.url', 'name' => 'URL'],
                    ['value' => 'article.author_id', 'name' => 'Feed ID'],
                    ['value' => 'article.author_type', 'name' => 'Feed Type'],
                    ['value' => 'article.published_at', 'name' => 'Published at'],
                    ['value' => 'article.last_modified', 'name' => 'Last modified'],
                ],
            ],
        ];
    }
}
