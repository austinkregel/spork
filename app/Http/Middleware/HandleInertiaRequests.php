<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Thread;
use App\Services\ConditionService;
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
            'navigation' => $navigation = (new ConditionService)->navigation(),
            'current_navigation' => $navigation->where('current', true)->first(),
            'conversations' => Thread::query()
                ->orderByDesc('origin_server_ts')
                ->paginate(
                    request('limit'),
                    ['*'],
                    'page',
                    request('page')
                ),
            'unread_email_count' => $request->user() ?
                $request->user()->messages()
                    ->where('messages.type', 'email')
                    ->where('seen', false)
                    ->count()
                : 0,
            'notifications' => $request->user()?->notifications ?? [],
        ]);
    }
}
