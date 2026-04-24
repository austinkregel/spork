<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\Services\Navigation\NavigationRegistryContract;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(
        private readonly NavigationRegistryContract $navigation_registry,
    ) {
    }

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
        $user = $request->user();
        $path = '/'.ltrim($request->path(), '/');
        $current_pillar = $this->navigation_registry->currentPillar($path);

        return array_merge(parent::share($request), [
            'unread_email_count' => 0,
            'notifications' => $user
                ?->notifications()
                ?->whereNull('read_at')
                ?->orderByDesc('created_at')
                ?->limit(10)
                ?->get() ?? [],
            'notification_count' => $user
                ?->notifications()
                ?->whereNull('read_at')
                ?->count() ?? [],
            'pillars' => $user ? $this->navigation_registry->pillarsPayload($user) : [],
            'current_pillar' => $current_pillar?->value,
            'sub_nav' => $user && $current_pillar
                ? $this->navigation_registry->subNavForPillar($current_pillar, $user)
                : [],
            'breadcrumbs' => [],
        ]);
    }
}
