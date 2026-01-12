<?php

declare(strict_types=1);

namespace App\Projects;

use App\Models\Automation;
use App\Models\Credential;
use App\Models\Domain;
use App\Models\ExternalRssFeed;
use App\Models\Finance\Account;
use App\Models\Finance\Budget;
use App\Models\Finance\Transaction;
use App\Models\Page;
use App\Models\Person;
use App\Models\Research;
use App\Models\Server;
use App\Models\Task;
use App\Models\Thread;

class ProjectTemplates
{
    /**
     * Map legacy template keys to current templates.
     */
    public function normalizeKey(?string $key): ?string
    {
        if ($key === null || $key === '') {
            return $key;
        }

        return match ($key) {
            ProjectTemplate::INFRA_DEPLOYMENT->value => ProjectTemplate::INFRASTRUCTURE_MONITORING->value,
            ProjectTemplate::RESEARCH_HUB->value => ProjectTemplate::CONTENT_RESEARCH->value,
            ProjectTemplate::PERSONAL_UPKEEP->value => ProjectTemplate::HOME_OPS->value,
            default => $key,
        };
    }

    /**
     * @return list<ProjectTemplateDefinition>
     */
    public function all(): array
    {
        return [
            new ProjectTemplateDefinition(
                template: ProjectTemplate::INFRASTRUCTURE_MONITORING,
                label: 'Infrastructure Monitoring',
                description: 'Monitor infrastructure health and group the servers/domains/credentials you operate.',
                sections: [
                    ['key' => 'servers', 'label' => 'Servers', 'allowed_resource_types' => [Server::class]],
                    ['key' => 'domains', 'label' => 'Domains', 'allowed_resource_types' => [Domain::class]],
                    ['key' => 'credentials', 'label' => 'Credentials', 'allowed_resource_types' => [Credential::class]],
                    ['key' => 'automations', 'label' => 'Automations', 'allowed_resource_types' => [Automation::class]],
                    ['key' => 'tasks', 'label' => 'Tasks', 'allowed_resource_types' => [Task::class]],
                ],
                preferred_resource_types: [
                    Server::class,
                    Domain::class,
                    Credential::class,
                    Automation::class,
                    Task::class,
                ],
            ),
            new ProjectTemplateDefinition(
                template: ProjectTemplate::CONTENT_RESEARCH,
                label: 'Content Research',
                description: 'Research a topic with sources, pages, RSS feeds, and follow-up tasks.',
                sections: [
                    ['key' => 'research', 'label' => 'Research', 'allowed_resource_types' => [Research::class]],
                    ['key' => 'pages', 'label' => 'Pages', 'allowed_resource_types' => [Page::class]],
                    ['key' => 'rss_feeds', 'label' => 'RSS Feeds', 'allowed_resource_types' => [ExternalRssFeed::class]],
                    ['key' => 'tasks', 'label' => 'Tasks', 'allowed_resource_types' => [Task::class]],
                ],
                preferred_resource_types: [
                    Research::class,
                    Page::class,
                    ExternalRssFeed::class,
                    Task::class,
                ],
            ),
            new ProjectTemplateDefinition(
                template: ProjectTemplate::FINANCE_TRACKING,
                label: 'Finance Tracking',
                description: 'Track budgets, accounts, and transactions across integrations, with tasks and automation.',
                sections: [
                    ['key' => 'budgets', 'label' => 'Budgets', 'allowed_resource_types' => [Budget::class]],
                    ['key' => 'accounts', 'label' => 'Accounts', 'allowed_resource_types' => [Account::class]],
                    ['key' => 'transactions', 'label' => 'Transactions', 'allowed_resource_types' => [Transaction::class]],
                    ['key' => 'credentials', 'label' => 'Credentials', 'allowed_resource_types' => [Credential::class]],
                    ['key' => 'tasks', 'label' => 'Tasks', 'allowed_resource_types' => [Task::class]],
                    ['key' => 'automations', 'label' => 'Automations', 'allowed_resource_types' => [Automation::class]],
                ],
                preferred_resource_types: [
                    Budget::class,
                    Account::class,
                    Transaction::class,
                    Credential::class,
                    Task::class,
                    Automation::class,
                ],
            ),
            new ProjectTemplateDefinition(
                template: ProjectTemplate::COMMUNICATION_HUB,
                label: 'Communication Hub',
                description: 'Organize people, threads, and follow-up tasks in one place.',
                sections: [
                    ['key' => 'people', 'label' => 'People', 'allowed_resource_types' => [Person::class]],
                    ['key' => 'threads', 'label' => 'Threads', 'allowed_resource_types' => [Thread::class]],
                    ['key' => 'tasks', 'label' => 'Tasks', 'allowed_resource_types' => [Task::class]],
                ],
                preferred_resource_types: [
                    Person::class,
                    Thread::class,
                    Task::class,
                ],
            ),
            new ProjectTemplateDefinition(
                template: ProjectTemplate::AUTOMATION_OPS,
                label: 'Automation Ops',
                description: 'Build and operate automations that act on the resources you attach here.',
                sections: [
                    ['key' => 'automations', 'label' => 'Automations', 'allowed_resource_types' => [Automation::class]],
                    ['key' => 'credentials', 'label' => 'Credentials', 'allowed_resource_types' => [Credential::class]],
                    ['key' => 'servers', 'label' => 'Servers', 'allowed_resource_types' => [Server::class]],
                    ['key' => 'domains', 'label' => 'Domains', 'allowed_resource_types' => [Domain::class]],
                    ['key' => 'tasks', 'label' => 'Tasks', 'allowed_resource_types' => [Task::class]],
                ],
                preferred_resource_types: [
                    Automation::class,
                    Credential::class,
                    Server::class,
                    Domain::class,
                    Task::class,
                ],
            ),
            new ProjectTemplateDefinition(
                template: ProjectTemplate::PERSONAL_CRM,
                label: 'Personal CRM',
                description: 'Keep a lightweight relationship hub: people, threads, and recurring follow-ups.',
                sections: [
                    ['key' => 'people', 'label' => 'People', 'allowed_resource_types' => [Person::class]],
                    ['key' => 'threads', 'label' => 'Threads', 'allowed_resource_types' => [Thread::class]],
                    ['key' => 'tasks', 'label' => 'Tasks', 'allowed_resource_types' => [Task::class]],
                ],
                preferred_resource_types: [
                    Person::class,
                    Thread::class,
                    Task::class,
                ],
            ),
            new ProjectTemplateDefinition(
                template: ProjectTemplate::HOME_OPS,
                label: 'Home Ops',
                description: 'A home operations workspace: tasks, budgets, and recurring routines.',
                sections: [
                    ['key' => 'tasks', 'label' => 'Tasks', 'allowed_resource_types' => [Task::class]],
                    ['key' => 'budgets', 'label' => 'Budgets', 'allowed_resource_types' => [Budget::class]],
                    ['key' => 'accounts', 'label' => 'Accounts', 'allowed_resource_types' => [Account::class]],
                    ['key' => 'transactions', 'label' => 'Transactions', 'allowed_resource_types' => [Transaction::class]],
                ],
                preferred_resource_types: [
                    Task::class,
                    Budget::class,
                    Account::class,
                    Transaction::class,
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
