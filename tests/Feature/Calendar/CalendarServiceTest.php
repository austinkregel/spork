<?php

declare(strict_types=1);

namespace Tests\Feature\Calendar;

use App\Models\Event;
use App\Models\Finance\Budget;
use App\Models\Task;
use App\Models\User;
use App\Services\Calendar\CalendarService;
use App\Services\Finance\BudgetPeriodHelper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarServiceTest extends TestCase
{
    use RefreshDatabase;

    private CalendarService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CalendarService(
            new BudgetPeriodHelper()
        );
    }

    public function test_get_events_for_date_range_returns_events(): void
    {
        $user = User::factory()->create();

        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        Event::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Event',
            'start_at' => $start->copy()->addDays(5),
            'end_at' => $start->copy()->addDays(5)->addHours(2),
        ]);

        $events = $this->service->getEventsForDateRange($user, $start, $end);

        $this->assertCount(1, $events);
        $this->assertEquals('Test Event', $events->first()['title']);
        $this->assertEquals('event', $events->first()['type']);
    }

    public function test_get_events_expands_recurring_events(): void
    {
        $user = User::factory()->create();

        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        Event::factory()->create([
            'user_id' => $user->id,
            'title' => 'Weekly Meeting',
            'start_at' => $start->copy()->addDays(1), // First Monday
            'end_at' => $start->copy()->addDays(1)->addHours(1),
            'rrule' => 'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO',
        ]);

        $events = $this->service->getEventsForDateRange($user, $start, $end);

        // Should have multiple occurrences (one per week in the month)
        $this->assertGreaterThan(1, $events->count());
        $weeklyMeetings = $events->filter(fn ($e) => $e['title'] === 'Weekly Meeting');
        $this->assertGreaterThan(1, $weeklyMeetings->count());
    }

    public function test_get_events_includes_tasks(): void
    {
        $user = User::factory()->create();
        $project = $user->personalProjects()->create(['name' => 'Test Project']);

        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        Task::factory()->create([
            'project_id' => $project->id,
            'name' => 'Project Task',
            'start_date' => $start->copy()->addDays(10),
            'end_date' => $start->copy()->addDays(10)->addHours(3),
        ]);

        $events = $this->service->getEventsForDateRange($user, $start, $end);

        $taskEvents = $events->filter(fn ($e) => $e['type'] === 'task');
        $this->assertGreaterThan(0, $taskEvents->count());
        $this->assertEquals('Project Task', $taskEvents->first()['title']);
    }

    public function test_get_events_includes_budget_periods(): void
    {
        $user = User::factory()->create();

        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        Budget::factory()->create([
            'user_id' => $user->id,
            'name' => 'Monthly Budget',
            'frequency' => Budget::FREQUENCY_MONTHLY,
            'interval' => 1,
            'started_at' => $start,
        ]);

        $events = $this->service->getEventsForDateRange($user, $start, $end);

        $budgetEvents = $events->filter(fn ($e) => $e['type'] === 'budget');
        $this->assertGreaterThan(0, $budgetEvents->count());
        $this->assertStringContainsString('Monthly Budget', $budgetEvents->first()['title']);
    }

    public function test_get_events_filters_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        Event::factory()->create([
            'user_id' => $user1->id,
            'title' => 'User 1 Event',
            'start_at' => $start->copy()->addDays(5),
            'end_at' => $start->copy()->addDays(5)->addHours(2),
        ]);

        Event::factory()->create([
            'user_id' => $user2->id,
            'title' => 'User 2 Event',
            'start_at' => $start->copy()->addDays(5),
            'end_at' => $start->copy()->addDays(5)->addHours(2),
        ]);

        $events = $this->service->getEventsForDateRange($user1, $start, $end);

        $this->assertCount(1, $events);
        $this->assertEquals('User 1 Event', $events->first()['title']);
    }

    public function test_get_events_returns_empty_collection_when_no_events(): void
    {
        $user = User::factory()->create();

        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $events = $this->service->getEventsForDateRange($user, $start, $end);

        $this->assertCount(0, $events);
    }
}
