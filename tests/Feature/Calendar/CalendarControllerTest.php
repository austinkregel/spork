<?php

declare(strict_types=1);

namespace Tests\Feature\Calendar;

use App\Models\Event;
use App\Models\Finance\Budget;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['broadcasting.default' => 'null']);
    }

    public function test_calendar_index_route_is_accessible(): void
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/communication/calendar');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Calendar/Index')
            ->has('title')
        );
    }

    public function test_calendar_fullscreen_route_is_accessible(): void
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/communication/calendar/fullscreen');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Calendar/FullScreen')
            ->has('title')
        );
    }

    public function test_events_api_returns_events_for_date_range(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        Event::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Event',
            'start_at' => $start->copy()->addDays(5),
            'end_at' => $start->copy()->addDays(5)->addHours(2),
        ]);

        $response = $this->getJson(
            'http://spork.localhost/api/calendar/events?'.http_build_query([
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
            ])
        );

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'events' => [
                '*' => ['id', 'title', 'start', 'end', 'type', 'color'],
            ],
        ]);
        $response->assertJsonCount(1, 'events');
    }

    public function test_events_api_requires_valid_date_range(): void
    {
        $this->actingAsUser();

        $response = $this->getJson('http://spork.localhost/api/calendar/events');

        $response->assertStatus(422);
    }

    public function test_can_create_event(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $start = Carbon::now()->addDays(1);
        $end = $start->copy()->addHours(2);

        $response = $this->postJson('http://spork.localhost/api/calendar/events', [
            'title' => 'New Event',
            'description' => 'Event description',
            'start_at' => $start->toIso8601String(),
            'end_at' => $end->toIso8601String(),
            'color' => '#3b82f6',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['event']);

        $this->assertDatabaseHas('events', [
            'user_id' => $user->id,
            'title' => 'New Event',
            'description' => 'Event description',
        ]);
    }

    public function test_can_create_recurring_event(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $start = Carbon::now()->addDays(1);
        $end = $start->copy()->addHours(2);

        $response = $this->postJson('http://spork.localhost/api/calendar/events', [
            'title' => 'Recurring Event',
            'start_at' => $start->toIso8601String(),
            'end_at' => $end->toIso8601String(),
            'rrule' => 'FREQ=WEEKLY;INTERVAL=1',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('events', [
            'user_id' => $user->id,
            'title' => 'Recurring Event',
            'rrule' => 'FREQ=WEEKLY;INTERVAL=1',
        ]);
    }

    public function test_can_update_event(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $event = Event::factory()->create([
            'user_id' => $user->id,
            'title' => 'Original Title',
        ]);

        $response = $this->putJson("http://spork.localhost/api/calendar/events/{$event->id}", [
            'title' => 'Updated Title',
            'start_at' => $event->start_at->toIso8601String(),
            'end_at' => $event->end_at->toIso8601String(),
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_cannot_update_other_users_event(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($user);

        $event = Event::factory()->create([
            'user_id' => $otherUser->id,
            'title' => 'Other User Event',
        ]);

        $response = $this->putJson("http://spork.localhost/api/calendar/events/{$event->id}", [
            'title' => 'Hacked Title',
            'start_at' => $event->start_at->toIso8601String(),
            'end_at' => $event->end_at->toIso8601String(),
        ]);

        $response->assertStatus(403);
    }

    public function test_can_delete_event(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $event = Event::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->deleteJson("http://spork.localhost/api/calendar/events/{$event->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function test_cannot_delete_other_users_event(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($user);

        $event = Event::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->deleteJson("http://spork.localhost/api/calendar/events/{$event->id}");

        $response->assertStatus(403);
    }

    public function test_events_api_includes_tasks(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $project = $user->personalProjects()->create(['name' => 'Test Project']);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'name' => 'Test Task',
            'start_date' => Carbon::now()->addDays(3),
            'end_date' => Carbon::now()->addDays(3)->addHours(4),
        ]);

        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $response = $this->getJson(
            'http://spork.localhost/api/calendar/events?'.http_build_query([
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
            ])
        );

        $response->assertStatus(200);
        $events = $response->json('events');
        $taskEvent = collect($events)->firstWhere('type', 'task');
        $this->assertNotNull($taskEvent);
        $this->assertEquals('Test Task', $taskEvent['title']);
    }

    public function test_events_api_includes_budget_periods(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'name' => 'Monthly Budget',
            'frequency' => Budget::FREQUENCY_MONTHLY,
            'interval' => 1,
            'started_at' => Carbon::now()->startOfMonth(),
        ]);

        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $response = $this->getJson(
            'http://spork.localhost/api/calendar/events?'.http_build_query([
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
            ])
        );

        $response->assertStatus(200);
        $events = $response->json('events');
        $budgetEvent = collect($events)->firstWhere('type', 'budget');
        $this->assertNotNull($budgetEvent);
        $this->assertStringContainsString('Monthly Budget', $budgetEvent['title']);
    }
}
