<?php

declare(strict_types=1);

namespace Tests\Feature\Dav;

use App\Models\Event;
use App\Models\User;
use App\Services\Dav\Sources\EventCalDavSource;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CalDavReadWriteTest extends DavTestCase
{
    use RefreshDatabase;

    public function test_recurring_event_round_trip_through_caldav(): void
    {
        $owner = User::factory()->create();
        $token = $this->tokenFor($owner);

        $vcal = $this->vcalendar(
            uid: 'weekly-standup-uid',
            summary: 'Weekly Standup',
            start: '2026-04-20T15:00:00Z',
            end: '2026-04-20T15:30:00Z',
            rrule: 'FREQ=WEEKLY;BYDAY=MO',
        );

        $put = $this->davRequest(
            'PUT',
            '/dav/calendars/'.$owner->id.'/default/weekly-standup-uid.ics',
            $token,
            $vcal,
            ['Content-Type' => 'text/calendar'],
        );
        $this->assertContains($put->getStatusCode(), [201, 204]);

        $event = Event::query()->where('user_id', $owner->id)->first();
        $this->assertNotNull($event);
        $this->assertSame('Weekly Standup', $event->title);
        $this->assertSame('FREQ=WEEKLY;BYDAY=MO', $event->rrule);
        $this->assertSame('weekly-standup-uid', $event->identifiers[EventCalDavSource::UID_KEY]);
        $this->assertTrue($event->isRecurring());

        $getResponse = $this->davRequest(
            'GET',
            '/dav/calendars/'.$owner->id.'/default/weekly-standup-uid.ics',
            $token,
        );
        $getResponse->assertStatus(200);
        $body = $getResponse->getContent();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $body);
        $this->assertStringContainsString('SUMMARY:Weekly Standup', $body);
        $this->assertStringContainsString('RRULE:FREQ=WEEKLY;BYDAY=MO', $body);
    }

    public function test_event_can_be_deleted_via_caldav(): void
    {
        $owner = User::factory()->create();
        $token = $this->tokenFor($owner);

        $event = Event::factory()->create([
            'user_id' => $owner->id,
            'identifiers' => [EventCalDavSource::UID_KEY => 'gone-soon'],
            'start_at' => Carbon::parse('2026-05-01T09:00:00Z'),
            'end_at' => Carbon::parse('2026-05-01T10:00:00Z'),
        ]);

        $response = $this->davRequest(
            'DELETE',
            '/dav/calendars/'.$owner->id.'/default/gone-soon.ics',
            $token,
        );

        $this->assertContains($response->getStatusCode(), [200, 204]);
        $this->assertNull(Event::query()->find($event->id));
    }

    public function test_put_without_dav_write_is_rejected(): void
    {
        $owner = User::factory()->create();
        $token = $this->tokenFor($owner, ['dav:read']);

        $vcal = $this->vcalendar(
            uid: 'forbidden-event',
            summary: 'Nope',
            start: '2026-05-01T09:00:00Z',
            end: '2026-05-01T10:00:00Z',
        );

        $response = $this->davRequest(
            'PUT',
            '/dav/calendars/'.$owner->id.'/default/forbidden-event.ics',
            $token,
            $vcal,
            ['Content-Type' => 'text/calendar'],
        );

        $this->assertContains($response->getStatusCode(), [401, 403]);
        $this->assertSame(0, Event::query()->where('user_id', $owner->id)->count());
    }

    private function vcalendar(string $uid, string $summary, string $start, string $end, ?string $rrule = null): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Spork//Test//EN',
            'BEGIN:VEVENT',
            'UID:'.$uid,
            'SUMMARY:'.$summary,
            'DTSTART:'.$this->iso($start),
            'DTEND:'.$this->iso($end),
            'DTSTAMP:'.$this->iso('2026-04-17T12:00:00Z'),
        ];

        if ($rrule) {
            $lines[] = 'RRULE:'.$rrule;
        }

        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';
        $lines[] = '';

        return implode("\r\n", $lines);
    }

    private function iso(string $value): string
    {
        return Carbon::parse($value)->utc()->format('Ymd\\THis\\Z');
    }
}
