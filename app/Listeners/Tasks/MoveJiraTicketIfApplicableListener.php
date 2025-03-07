<?php

declare(strict_types=1);

namespace App\Listeners\Tasks;

use App\Contracts\Services\JiraServiceContract;
use App\Events\Models\Task\TaskUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;

class MoveJiraTicketIfApplicableListener implements ShouldQueue
{
    public function __construct(
        protected JiraServiceContract $jiraService
    ) {}

    public function handle(TaskUpdated $updatedTask): void
    {
        $task = $updatedTask->model;
        $task->load('project');

        if (isset($task->service_identifier)) {
            $taskName = explode(' - ', $task->name, 2);

            $this->jiraService->updateTicket($taskName[0], [
                'status' => $task->status,
            ]);
        }
    }
}
