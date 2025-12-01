<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Models\Tag;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class AutomationController
{
    public function index()
    {
        return Inertia::render('Automation/Index', [
            'title' => 'Automation control center',
            'subnavigation' => $this->navigation(),
            'blueprints' => [
                [
                    'name' => 'Automation operations',
                    'summary' => 'Model recurring automations as Dealer Inspire operations with stored inputs and replayable payloads.',
                    'items' => [
                        'Define an "automation" operation type that captures crawler intent, source URLs, authentication requirements, and output targets.',
                        'Persist reusable payloads (e.g., curl recipes, Dusk scripts, parsers) with tags to gate credentials and environment access.',
                        'Emit detailed activity logs and telemetry for each run to feed observability and health dashboards.',
                    ],
                ],
                [
                    'name' => 'Execution graph',
                    'summary' => 'Compose playbooks from steps that can call HTTP crawlers, Laravel Dusk flows, or internal jobs.',
                    'items' => [
                        'Represent playbooks as ordered, typed steps with inputs, guards, and retry rules.',
                        'Allow steps to call into Dusk browser sessions for interactive flows or the existing Guzzle crawler for lighter probes.',
                        'Expose a dry-run mode that captures requests without executing write actions, enabling safe iteration.',
                    ],
                ],
                [
                    'name' => 'Safety + pacing',
                    'summary' => 'Throttle by host and tag to mimic human traffic and avoid accidental denial of service.',
                    'items' => [
                        'Enforce minimum delay windows per host using cache-backed buckets and progressive backoff.',
                        'Limit concurrency per automation tag (e.g., finance, shopping, civic) so high-priority runs do not starve others.',
                        'Record robots.txt stance, user-agent, and contact metadata for each crawler to simplify responsible use.',
                    ],
                ],
                [
                    'name' => 'Scheduling + distribution',
                    'summary' => 'Cadence-aware scheduling keeps long-running crawls staggered with calendar-like visibility.',
                    'items' => [
                        'Provide daily, weekly, and custom cron cadences mapped to queues sized for external traffic.',
                        'Randomize start offsets and batch sizes to spread load while maintaining SLAs.',
                        'Surface a per-automation timeline that shows next run, last run, duration, and output size.',
                    ],
                ],
            ],
            'pipelines' => [
                [
                    'name' => 'Site monitors',
                    'description' => 'Watch for price changes, stock availability, or content edits with diff-friendly storage.',
                ],
                [
                    'name' => 'Statement fetchers',
                    'description' => 'Log into billing portals with Dusk to pull monthly PDFs or balances.',
                ],
                [
                    'name' => 'News + civic sweeps',
                    'description' => 'Collect headlines from local outlets lacking feeds and tag by topic for follow-up.',
                ],
                [
                    'name' => 'Inventory scouts',
                    'description' => 'Scan eCommerce listings for back-in-stock signals and route notifications.',
                ],
            ],
            'safety' => [
                'Global pacing caps per domain and per automation tag.',
                'Optional warm-up runs using HEAD/OPTIONS before full fetches.',
                'Audit trail for cookies, headers, and credential usage per run.',
            ],
            'integrations' => [
                'Laravel Dusk sessions for stateful browser automations.',
                'Existing curl/Guzzle crawler for lightweight harvesting.',
                'Operations package for orchestration, retries, and logging.',
            ],
        ]);
    }

    public function tags()
    {
        $tags = auth()->user()->tags()->withSum('transactions', 'amount')
            ->with(['conditions'])
            ->orderBy('type')
            ->paginate(
                request('limit', 1000),
                ['*'],
                'page',
                request('page')
            );

        $tagsWithCounts = array_map(
            function (Tag $tag) {
                $tag->setAttribute('taggables_count', $tag->tagged()->count());

                return $tag;
            },
            $tags->items()
        );

        return Inertia::render('Automation/Tags', [
            'title' => 'Automation tags',
            'subnavigation' => $this->navigation(),
            'tags' => new LengthAwarePaginator(
                $tagsWithCounts,
                $tags->total(),
                $tags->perPage(),
                $tags->currentPage(),
                ['path' => request()->url(), 'query' => request()->query()]
            ),
        ]);
    }

    public function show(Tag $tag)
    {
        abort_unless(
            auth()->user()->tags()->whereKey($tag->getKey())->exists(),
            404,
            'Tag not found'
        );

        $tag->setAttribute('taggables_count', $tag->tagged()->count());

        return Inertia::render('Automation/TagShow', [
            'title' => 'Automation tag detail',
            'subnavigation' => $this->navigation(),
            'tag' => $tag->loadSum('transactions', 'amount')
                ->load([
                    'conditions',
                    'articles' => function ($q) {
                        $q->latest('last_modified');
                    },
                    'feeds' => function ($q) {
                        $q->latest('last_modified');
                    },
                    'servers',
                    'transactions' => function ($q) {
                        $q->latest('date');
                    },
                    'projects',
                    'budgets',
                    'accounts',
                    'domains',
                    'people',
                    'messages',
                ]),
            'type' => Tag::class,
        ]);
    }

    protected function navigation(): Collection
    {
        return Collection::make([
            [
                'name' => 'Overview',
                'href' => '/-/automation',
                'icon' => 'Cog8ToothIcon',
                'slug' => 'overview',
            ],
            [
                'name' => 'Automations',
                'href' => '/-/automation/automations',
                'icon' => 'BoltIcon',
                'slug' => 'automations',
            ],
            [
                'name' => 'Tags + routing',
                'href' => '/-/automation/tags',
                'icon' => 'TagIcon',
                'slug' => 'tags',
            ],
            [
                'name' => 'Playbooks (planned)',
                'href' => '/-/automation#playbooks',
                'icon' => 'DocumentTextIcon',
                'slug' => 'playbooks',
            ],
            [
                'name' => 'Schedules (planned)',
                'href' => '/-/automation#scheduling',
                'icon' => 'CalendarDaysIcon',
                'slug' => 'scheduling',
            ],
        ]);
    }
}
