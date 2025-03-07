<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Services\JiraServiceContract;
use App\Models\Project;
use Illuminate\Support\Arr;
use JiraRestApi\Project\Project as JiraProject;

class ProjectRepository
{
    public function __construct(
        protected JiraServiceContract $jira
    ) {}

    public function createJiraProject(JiraProject $jiraProject, $page = 1): Project
    {
        $mapping = [
            'id' => 'jira_id',
            'name' => 'name',
            'key' => 'key',
            'projectCategory.name' => 'category_name',
            'isPrivate' => 'should_not_count_labor',
            'simplified' => 'is_simplified',
        ];

        $data = [];
        foreach ($mapping as $jiraField => $myField) {
            $data[$myField] = Arr::get($jiraProject->toArray(), $jiraField);
        }

        return Project::query()
            ->firstOrCreate([
                'name' => $data['key'],
            ], [
                'name' => $data['key'],
                'settings' => [
                    'jira_id' => $data['jira_id'],
                    'jira_name' => $data['name'],
                ],
                'team_id' => 1,
            ]);
    }
}
