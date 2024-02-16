<?php
declare(strict_types=1);

namespace App\Contracts\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use JiraRestApi\Sprint\Sprint;

interface JiraServiceContract
{
    public function findAllTickets(string $project, Carbon $createdAt, int $limit = 15, int $page = 1);

    public function findOneTicket(string $ticketName);

    /**
     * @param string $sprint
     * @return Sprint
     */
    public function findSprint(string $sprint);

    public function findAllSprints(string $projectKey): array;

    public function findAllProjects(int $limit = 50, int $page = 1): LengthAwarePaginator;
}
