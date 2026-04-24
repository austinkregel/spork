<?php

declare(strict_types=1);

namespace App\Services\Dav\Sources;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Reader;

class EventCalDavSource extends AbstractEloquentSource
{
    public const UID_KEY = 'vcalendar_uid';

    public function modelClass(): string
    {
        return Event::class;
    }

    public function collectionType(): string
    {
        return self::TYPE_CALENDAR;
    }

    public function putObject(User $user, string $collection, string $uri, string $body): string
    {
        $vcal = Reader::read($body);

        if (! $vcal instanceof VCalendar) {
            throw new \Sabre\DAV\Exception\UnsupportedMediaType('Body is not a VCalendar');
        }

        $vevent = $vcal->VEVENT;

        if (! $vevent) {
            throw new \Sabre\DAV\Exception\UnsupportedMediaType('VCalendar does not contain a VEVENT');
        }

        $uid = (string) ($vevent->UID ?? $this->stripUriExtension($uri));
        $uid = $uid === '' ? (string) Str::uuid() : $uid;

        $event = Event::query()
            ->where('user_id', $user->getKey())
            ->where('identifiers->'.self::UID_KEY, $uid)
            ->first();

        if (! $event) {
            $event = new Event;
            $event->user_id = $user->getKey();
        }

        $identifiers = (array) ($event->identifiers ?? []);
        $identifiers[self::UID_KEY] = $uid;
        $event->identifiers = $identifiers;
        $event->title = (string) ($vevent->SUMMARY ?? 'Untitled');
        $event->description = isset($vevent->DESCRIPTION) ? (string) $vevent->DESCRIPTION : null;
        $event->start_at = Carbon::parse((string) $vevent->DTSTART->getDateTime()->format(\DateTimeInterface::ATOM));

        if (isset($vevent->DTEND)) {
            $event->end_at = Carbon::parse((string) $vevent->DTEND->getDateTime()->format(\DateTimeInterface::ATOM));
        } else {
            $event->end_at = $event->start_at->copy()->addHour();
        }

        $event->rrule = isset($vevent->RRULE) ? (string) $vevent->RRULE : null;

        if (isset($vevent->COLOR)) {
            $event->color = (string) $vevent->COLOR;
        }

        $event->save();

        $this->syncCollectionTag($event, $collection);

        return $this->etagFor($event->fresh());
    }

    protected function defaultDisplayName(): string
    {
        return 'Calendar';
    }

    protected function defaultDescription(): string
    {
        return 'Default calendar';
    }

    protected function uidColumn(): string
    {
        return 'identifiers';
    }

    protected function uidJsonKey(): string
    {
        return self::UID_KEY;
    }

    protected function objectUriFor(Model $model): string
    {
        $identifiers = (array) ($model->identifiers ?? []);
        $uid = $identifiers[self::UID_KEY] ?? null;

        if (! $uid) {
            $uid = (string) Str::uuid();
            $identifiers[self::UID_KEY] = $uid;
            $model->identifiers = $identifiers;
            $model->saveQuietly();
        }

        return $uid.'.ics';
    }

    protected function renderObjectBody(Model $model): string
    {
        return $this->eventToVCalendar($model)->serialize();
    }

    protected function stripUriExtension(string $uri): string
    {
        if (str_ends_with($uri, '.ics')) {
            return substr($uri, 0, -4);
        }

        return $uri;
    }

    private function eventToVCalendar(Event $event): VCalendar
    {
        $identifiers = (array) ($event->identifiers ?? []);
        $uid = $identifiers[self::UID_KEY] ?? null;

        if (! $uid) {
            $uid = (string) Str::uuid();
            $identifiers[self::UID_KEY] = $uid;
            $event->identifiers = $identifiers;
            $event->saveQuietly();
        }

        $veventData = [
            'UID' => $uid,
            'SUMMARY' => (string) ($event->title ?? ''),
            'DTSTAMP' => $event->updated_at?->copy()->utc() ?? Carbon::now()->utc(),
            'DTSTART' => $event->start_at->copy()->utc(),
            'DTEND' => $event->end_at->copy()->utc(),
        ];

        if ($event->description) {
            $veventData['DESCRIPTION'] = $event->description;
        }

        if ($event->rrule) {
            $veventData['RRULE'] = $event->rrule;
        }

        $vcal = new VCalendar([
            'PRODID' => '-//Spork//CalDAV//EN',
            'VEVENT' => $veventData,
        ]);

        if ($event->color) {
            $vcal->VEVENT->add('COLOR', $event->color);
        }

        return $vcal;
    }

    private function syncCollectionTag(Event $event, string $collection): void
    {
        $event->syncTagsWithType(
            $collection === self::DEFAULT_COLLECTION ? [] : [$collection],
            self::COLLECTION_TAG_NAMESPACE,
        );
    }
}
