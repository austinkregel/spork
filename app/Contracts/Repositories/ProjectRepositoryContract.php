<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Project;
use JiraRestApi\Project\Project as JiraProject;

interface ProjectRepositoryContract
{
    public function createJiraProject(JiraProject $jiraProject, $page = 1): Project;
}
