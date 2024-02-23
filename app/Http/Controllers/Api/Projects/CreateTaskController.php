<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;

class CreateTaskController extends Controller
{
    public function __invoke(UpdateProjectRequest $request, Project $project)
    {
        return $project->tasks()->create($request->validated());
    }
}
