<?php

declare(strict_types=1);

namespace App\Projects;

use App\Models\Automation;
use App\Models\Credential;
use App\Models\Deployment;
use App\Models\Domain;
use App\Models\ExternalRssFeed;
use App\Models\Page;
use App\Models\Person;
use App\Models\Research;
use App\Models\Server;
use App\Models\Task;
use App\Models\Thread;
use App\Models\Finance\Account;
use App\Models\Finance\Budget;
use App\Models\Finance\Transaction;

class ProjectTemplates
{
    /**
     * @return list<ProjectTemplateDefinition>
     */
    public function all(): array
    {
        return [
            new ProjectTemplateDefinition(
                template: ProjectTemplate::INFRA_DEPLOYMENT,
                label: 'Infrastructure / Deployment',
                description: 'Organize servers, domains, deployments, and credentials around a deployable app or site.',
                sections: [
                    ['key' => 'infrastructure', 'label' => 'Infrastructure', 'allowed_resource_groups' => ['infrastructure']],
                    ['key' => 'integrations', 'label' => 'Integrations', 'allowed_resource_groups' => ['integrations']],
                    ['key' => 'tasks', 'label' => 'Tasks', 'allowed_resource_groups' => ['tasks']],
                ],
                preferred_resource_types: [
                    Deployment::class,
                    Server::class,
                    Domain::class,
                    Credential::class,
                    Task::class,
                ],
            ),
            new ProjectTemplateDefinition(
                template: ProjectTemplate::RESEARCH_HUB,
                label: 'Research Hub',
                description: 'Track research, pages, RSS feeds, and related tasks around a topic.',
                sections: [
                    ['key' => 'research', 'label' => 'Research', 'allowed_resource_groups' => ['research']],
                    ['key' => 'rss', 'label' => 'RSS', 'allowed_resource_groups' => ['rss']],
                    ['key' => 'tasks', 'label' => 'Tasks', 'allowed_resource_groups' => ['tasks']],
                ],
                preferred_resource_types: [
                    Research::class,
                    Page::class,
                    ExternalRssFeed::class,
                    Task::class,
                ],
            ),
            new ProjectTemplateDefinition(
                template: ProjectTemplate::PERSONAL_UPKEEP,
                label: 'Personal Upkeep',
                description: 'A recurring personal maintenance project: tasks, budgets, and the people involved.',
                sections: [
                    ['key' => 'tasks', 'label' => 'Tasks', 'allowed_resource_groups' => ['tasks']],
                    ['key' => 'finance', 'label' => 'Finance', 'allowed_resource_groups' => ['finance']],
                    ['key' => 'people', 'label' => 'People & Comms', 'allowed_resource_groups' => ['people']],
                ],
                preferred_resource_types: [
                    Task::class,
                    Budget::class,
                    Account::class,
                    Transaction::class,
                    Person::class,
                    Thread::class,
                ],
            ),
            new ProjectTemplateDefinition(
                template: ProjectTemplate::CUSTOM,
                label: 'Custom',
                description: 'Start blank and attach anything you need.',
                sections: [
                    ['key' => 'all', 'label' => 'Resources', 'allowed_resource_groups' => []],
                ],
                preferred_resource_types: [
                    Task::class,
                ],
            ),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forFrontend(): array
    {
        return array_map(
            fn (ProjectTemplateDefinition $definition) => $definition->toArray(),
            $this->all(),
        );
    }
}


