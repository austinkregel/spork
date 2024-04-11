<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Http\Controllers\Controller;
use App\Models\JobBatch;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Arr;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $person = Person::whereJsonContains('emails', auth()->user()->email)
            // for now, this is fine, my email base does support this idea, but I know if someone/
            // wanted to be malicious they could take advantage of this.
            ->first();

        return Inertia::render('Dashboard', [
            'project_count' => \App\Models\Project::count(),
            'server_count' => \App\Models\Server::count(),
            'domain_count' => \App\Models\Domain::count(),
            'credential_count' => \App\Models\Credential::count(),
            'user_count' => \App\Models\User::count(),
            // Unread Messages
            // Tasks due today
            // Domains that expire this month, or in the last 7 days
            // Weather at my primary address
            'weather' => $person->primary_address ? Arr::first(app(\App\Contracts\Services\WeatherServiceContract::class)->query(
                $person->primary_address,
            )) : null,

            'news' => (\App\Models\Article::query()
                ->with('externalRssFeed.tags')
                ->whereHas('externalRssFeed', function ($query) {
                    $query->where('owner_type', User::class)
                        ->where('owner_id', auth()->id());

                    $query->whereHas('tags', fn ($q) => $q->where('name->en', 'news'));
                })
                ->orderByDesc('last_modified')
                ->paginate(request('news_limit', 15), ['*'], 'news_page', request('news_page', 1))),

            'video_feed' => \App\Models\Article::query()
                ->with('externalRssFeed.tags')
                ->whereHas('externalRssFeed', function ($query) {
                    $query->where('owner_type', User::class)
                        ->where('owner_id', auth()->id());

                    $query->whereHas('tags', fn ($q) => $q->where('name->en', 'video'));
                })
                ->orderByDesc('last_modified')
                ->paginate(request('video_limit', 15), ['*'], 'video_page', request('video_page', 1)),
            'expiring_domains' => auth()->user()
                ->domains()
                ->where('expires_at', '>=', now()->subDays(7))
                ->where('expires_at', '<=', now()->addWeeks(4))
                ->orderByDesc('expires_at')
                ->paginate(request('expiring_limit', 15), ['*'], 'expiring_page', request('expiring_page', 1)),
            'job_batches' => JobBatch::query()
                ->orderByDesc('created_at')
                ->paginate(request('job_limit', 10), ['*'], 'job_page', request('job_page', 1)),
        ]);
    }
}
