<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\JiraServiceContract;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use JiraRestApi\Board\Board;
use JiraRestApi\Board\BoardService;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\Project\Project;
use JiraRestApi\Project\ProjectService;
use JiraRestApi\Sprint\Sprint;
use JiraRestApi\Sprint\SprintService;
use JiraRestApi\User\UserService;

class JiraService implements JiraServiceContract
{
    /**
     * @var ProjectService
     */
    protected $projectService;

    /**
     * @var IssueService
     */
    protected $issueService;

    /**
     * @var SprintService
     */
    private $sprintService;

    /**
     * @var BoardService
     */
    private $boardService;

    /**
     * @var UserService
     */
    private $userService;

    public function __construct(
        ProjectService $projectService,
        IssueService $issueService,
        SprintService $sprintService,
        BoardService $boardService,
        UserService $userService
    ) {
        $this->projectService = $projectService;
        $this->issueService = $issueService;
        $this->sprintService = $sprintService;
        $this->boardService = $boardService;
        $this->userService = $userService;
    }

    public function findAllTickets(string $project, Carbon $createdAt, int $limit = 15, int $page = 1): LengthAwarePaginator
    {
        $issues = $this->issueService->search("project = \"$project\" and updated >= {$createdAt->format('Y-m-d')} ORDER BY updated ASC", ($page * $limit) - $limit, $limit, [], [
            'changelog',
        ]);

        return new LengthAwarePaginator($issues->getIssues(), $issues->total, $limit, $page);
    }

    public function findOneTicket(string $ticketName)
    {
        return $this->issueService->get($ticketName);
    }

    public function findSprint(string $sprint): Sprint
    {
        return $this->sprintService->getSprint($sprint);
    }

    /**
     * Jira is a little dumb here... They only associate sprints with boards
     * so to get the sprints for a project, we have to get all the boards first, then
     * loop through all the boards that support sprints, to get the sprints.
     *
     * @return Sprint[]
     */
    public function findAllSprints(string $projectKey): array
    {
        /** @var \ArrayObject $boardList */
        $boardList = $this->boardService->getBoardList([
            'projectKeyOrId' => $projectKey,
            'type' => 'scrum',
        ]);
        $boards = array_map(function (Board $board) {
            return $board->getId();
        }, $boardList->getArrayCopy());

        /** @var \ArrayObject[] $boardSprints */
        $boardSprints = array_map(function (int $board) {
            return $this->boardService->getBoardSprints($board, [
                'maxResults' => 100,
            ])->getArrayCopy();
        }, $boards);

        return collect($boardSprints)->flatten()
            ->unique('id')
            ->toArray();
    }

    /**
     * @return array|Project[]
     */
    public function findAllProjects(int $limit = 50, int $page = 1): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $projects = $this->projectService->getAllProjects([
            'startAt' => $limit * ($page - 1),
            'maxResults' => $limit,
        ])->getArrayCopy();

        return new LengthAwarePaginator($projects, count($projects), $limit, $page);
    }

    public function findUser(string $accountId)
    {
        return $this->userService->get(['accountId' => $accountId]);
    }
}
