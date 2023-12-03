<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Domain;
use App\Models\Navigation;
use App\Models\Thread;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'navigation' => Navigation::query()
                ->where('authentication_required', auth()->check())
                ->whereNull('parent_id')
                ->orderBy('order')
                ->get()

            ->map(function (Navigation $item) {
                $item->current = $item->href === request()->getRequestUri() || ($item->children->isNotEmpty() && $item->children->filter(fn ($item) => $item->href === request()->getRequestUri())->count() > 0);
                return $item->toArray();
            }),
            'current_navigation' => Navigation::query()
                ->with(['parent.children', 'children' => function ($query) {
                    $query->orderBy('order');
                }])
                ->where('authentication_required', auth()->check())
                ->where('href', '/'.request()->path())
                ->orderBy('order')
                ->first(),
            'conversations' => Thread::query()
                ->orderByDesc('origin_server_ts')
                ->paginate(
                    request('limit'),
                    ['*'],
                    'page',
                    request('page')
                )
        ]);
    }
}
