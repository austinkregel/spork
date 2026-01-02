<?php

declare(strict_types=1);

use App\Models\Automation;
use App\Models\Credential;
use App\Models\Deployment;
use App\Models\Domain;
use App\Models\ExternalRssFeed;
use App\Models\Page;
use App\Models\Person;
use App\Models\Project;
use App\Models\Research;
use App\Models\Server;
use App\Models\Task;
use App\Models\Thread;
use App\Models\Finance\Account;
use App\Models\Finance\Budget;
use App\Models\Finance\Transaction;

return [
    /**
     * Allowed resource types that can be attached to projects.
     *
     * These power:
     * - server-side validation for attach/detach
     * - UI metadata (labels, grouping) for the Project Builder
     *
     * NOTE: Keep this list curated; do NOT allow arbitrary model classes.
     */
    'resources' => [
        Task::class => [
            'label' => 'Tasks',
            'group' => 'tasks',
        ],

        Budget::class => [
            'label' => 'Budgets',
            'group' => 'finance',
        ],
        Account::class => [
            'label' => 'Accounts',
            'group' => 'finance',
        ],
        Transaction::class => [
            'label' => 'Transactions',
            'group' => 'finance',
        ],

        Server::class => [
            'label' => 'Servers',
            'group' => 'infrastructure',
        ],
        Domain::class => [
            'label' => 'Domains',
            'group' => 'infrastructure',
        ],
        Deployment::class => [
            'label' => 'Deployments',
            'group' => 'infrastructure',
        ],

        Credential::class => [
            'label' => 'Credentials',
            'group' => 'integrations',
        ],

        Research::class => [
            'label' => 'Research',
            'group' => 'research',
        ],
        Page::class => [
            'label' => 'Pages',
            'group' => 'research',
        ],

        ExternalRssFeed::class => [
            'label' => 'RSS Feeds',
            'group' => 'rss',
        ],

        Person::class => [
            'label' => 'People',
            'group' => 'people',
        ],
        Thread::class => [
            'label' => 'Threads',
            'group' => 'people',
        ],

        Automation::class => [
            'label' => 'Automations',
            'group' => 'automation',
        ],
    ],

    /**
     * Human labels for groups shown in the Project Builder UI.
     */
    'groups' => [
        'tasks' => 'Tasks',
        'finance' => 'Finance',
        'infrastructure' => 'Infrastructure',
        'integrations' => 'Integrations',
        'research' => 'Research',
        'rss' => 'RSS',
        'people' => 'People & Comms',
        'automation' => 'Automation',
    ],
];


