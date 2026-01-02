<?php

declare(strict_types=1);

namespace App\Services\Projects;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ProjectAttachmentService
{
    public function __construct(
        private readonly ProjectResourceRegistry $registry,
        private readonly ConnectionInterface $db,
    ) {
    }

    public function attach(Project $project, string $resource_type, int $resource_id): void
    {
        $resource_type = $this->normalizeResourceType($resource_type);

        $this->ensureAllowed($resource_type);

        if ($resource_type === Task::class) {
            $this->attachTaskByProjectId($project, $resource_id);

            return;
        }

        if ($resource_type === Deployment::class) {
            $this->attachDeploymentByProjectId($project, $resource_id);

            return;
        }

        $exists = $this->db->table('project_resources')->where([
            'resource_type' => $resource_type,
            'resource_id' => $resource_id,
            'project_id' => $project->id,
        ])->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'resource_id' => ['Already attached to this project.'],
            ]);
        }

        $this->db->table('project_resources')->insert([
            'resource_type' => $resource_type,
            'resource_id' => $resource_id,
            'project_id' => $project->id,
            'settings' => json_encode($this->filterSettingsForResource($resource_type, $resource_id)),
        ]);
    }

    public function detach(Project $project, string $resource_type, int $resource_id): void
    {
        $resource_type = $this->normalizeResourceType($resource_type);

        $this->ensureAllowed($resource_type);

        if ($resource_type === Task::class) {
            $this->detachTaskByProjectId($project, $resource_id);

            return;
        }

        if ($resource_type === Deployment::class) {
            $this->detachDeploymentByProjectId($project, $resource_id);

            return;
        }

        $this->db->table('project_resources')->where([
            'resource_type' => $resource_type,
            'resource_id' => $resource_id,
            'project_id' => $project->id,
        ])->delete();
    }

    private function ensureAllowed(string $resource_type): void
    {
        if (! $this->registry->isAllowed($resource_type)) {
            throw ValidationException::withMessages([
                'resource_type' => ['This resource type is not attachable to projects.'],
            ]);
        }
    }

    /**
     * Normalize a resource type coming from HTTP.
     */
    private function normalizeResourceType(string $resource_type): string
    {
        return str_replace('\\\\', '\\', trim($resource_type));
    }

    /**
     * Derive pivot `settings` based on resource metadata.
     *
     * This intentionally stays lightweight: the goal is to help the UI avoid extra queries.
     */
    private function filterSettingsForResource(string $class_type, int $id): array
    {
        $settings = [];

        if (! class_exists($class_type)) {
            return $settings;
        }

        /** @var class-string<Model> $class_type */
        $relations = [];
        if (is_subclass_of($class_type, \App\Models\Taggable::class)) {
            $relations[] = 'tags';
        }

        /** @var Model|null $instance */
        $instance = $class_type::query()->with($relations)->find($id);
        if (! $instance) {
            return $settings;
        }

        if (method_exists($instance, 'tags') && $instance->relationLoaded('tags')) {
            // Mirror existing deployment behavior: detect special server roles via tags.
            $tags = $instance->getRelation('tags');
            $names = $tags?->map?->name?->map?->en;
            if ($names?->contains('loadbalancer')) {
                $settings['loadbalancer'] = true;
            }
            if ($names?->contains('default')) {
                $settings['default'] = true;
            }
        }

        return $settings;
    }

    private function attachTaskByProjectId(Project $project, int $task_id): void
    {
        /** @var Task|null $task */
        $task = Task::query()->find($task_id);

        if (! $task) {
            throw ValidationException::withMessages([
                'resource_id' => ['Task not found.'],
            ]);
        }

        if ($task->project_id === $project->id) {
            throw ValidationException::withMessages([
                'resource_id' => ['Already attached to this project.'],
            ]);
        }

        $task->project_id = $project->id;
        $task->save();
    }

    private function detachTaskByProjectId(Project $project, int $task_id): void
    {
        throw ValidationException::withMessages([
            'resource_type' => ['Tasks cannot be detached. Move the task to another project instead.'],
        ]);
    }

    private function attachDeploymentByProjectId(Project $project, int $deployment_id): void
    {
        /** @var Deployment|null $deployment */
        $deployment = Deployment::query()->find($deployment_id);

        if (! $deployment) {
            throw ValidationException::withMessages([
                'resource_id' => ['Deployment not found.'],
            ]);
        }

        if ($deployment->project_id === $project->id) {
            throw ValidationException::withMessages([
                'resource_id' => ['Already attached to this project.'],
            ]);
        }

        $deployment->project_id = $project->id;
        $deployment->save();
    }

    private function detachDeploymentByProjectId(Project $project, int $deployment_id): void
    {
        throw ValidationException::withMessages([
            'resource_type' => ['Deployments cannot be detached. Move the deployment to another project instead.'],
        ]);
    }
}


