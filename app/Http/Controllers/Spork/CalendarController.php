<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Http\Controllers\Controller;
use App\Http\Resources\Calendar\EventResource;
use App\Models\Event;
use App\Models\User;
use App\Services\Calendar\CalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function __construct(
        private CalendarService $calendarService
    ) {}

    public function index(): Response
    {
        return Inertia::render('Calendar/Index', [
            'title' => 'Calendar',
        ]);
    }

    public function fullscreen(): Response
    {
        return Inertia::render('Calendar/FullScreen', [
            'title' => 'Calendar - Full Screen',
        ]);
    }

    public function events(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start' => ['required', 'date'],
            'end' => ['required', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid date range'], 422);
        }

        /** @var User $user */
        $user = $request->user();
        $start = Carbon::parse($request->input('start'));
        $end = Carbon::parse($request->input('end'));

        $events = $this->calendarService->getEventsForDateRange($user, $start, $end);

        return response()->json([
            'events' => EventResource::collection($events->values()),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after_or_equal:start_at'],
            'rrule' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:7'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        /** @var User $user */
        $user = $request->user();

        $event = Event::create([
            'user_id' => $user->id,
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'start_at' => Carbon::parse($request->input('start_at')),
            'end_at' => Carbon::parse($request->input('end_at')),
            'rrule' => $request->input('rrule'),
            'color' => $request->input('color'),
        ]);

        return response()->json([
            'event' => new EventResource([
                'id' => 'event_'.$event->id,
                'title' => $event->title,
                'description' => $event->description,
                'start' => $event->start_at,
                'end' => $event->end_at,
                'type' => 'event',
                'color' => $event->color ?? '#3b82f6',
                'rrule' => $event->rrule,
                'recurring' => $event->isRecurring(),
                'model_id' => $event->id,
                'model_type' => Event::class,
            ]),
        ], 201);
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        // Ensure user owns the event
        if ($event->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_at' => ['sometimes', 'required', 'date'],
            'end_at' => ['sometimes', 'required', 'date', 'after_or_equal:start_at'],
            'rrule' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:7'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $event->update($request->only([
            'title',
            'description',
            'start_at',
            'end_at',
            'rrule',
            'color',
        ]));

        return response()->json([
            'event' => new EventResource([
                'id' => 'event_'.$event->id,
                'title' => $event->title,
                'description' => $event->description,
                'start' => $event->start_at,
                'end' => $event->end_at,
                'type' => 'event',
                'color' => $event->color ?? '#3b82f6',
                'rrule' => $event->rrule,
                'recurring' => $event->isRecurring(),
                'model_id' => $event->id,
                'model_type' => Event::class,
            ]),
        ]);
    }

    public function destroy(Request $request, Event $event): JsonResponse
    {
        // Ensure user owns the event
        if ($event->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $event->delete();

        return response()->json(['message' => 'Event deleted'], 200);
    }
}
