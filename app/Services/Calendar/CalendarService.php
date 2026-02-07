<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Models\Event;
use App\Models\Finance\Budget;
use App\Models\Task;
use App\Models\User;
use App\Operations\Operation;
use App\Services\Finance\BudgetPeriodHelper;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CalendarService
{
    public function __construct(
        private BudgetPeriodHelper $budgetPeriodHelper
    ) {}

    /**
     * Get all events for a user within the given date range.
     *
     * @return Collection<array{id: string, title: string, description: string|null, start: Carbon, end: Carbon, type: string, color: string|null, rrule: string|null, recurring: bool}>
     */
    public function getEventsForDateRange(User $user, Carbon $start, Carbon $end): Collection
    {
        $events = collect();

        // Get Event model events
        $eventModels = Event::query()
            ->where('user_id', $user->id)
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_at', [$start, $end])
                    ->orWhereBetween('end_at', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_at', '<=', $start)
                            ->where('end_at', '>=', $end);
                    });
            })
            ->get();

        foreach ($eventModels as $eventModel) {
            $occurrences = $eventModel->getOccurrences($start, $end);
            foreach ($occurrences as $occurrence) {
                $events->push([
                    'id' => 'event_'.$eventModel->id.'_'.md5($occurrence['start']->toIso8601String()),
                    'title' => $eventModel->title,
                    'description' => $eventModel->description,
                    'start' => $occurrence['start'],
                    'end' => $occurrence['end'],
                    'type' => 'event',
                    'color' => $eventModel->color ?? '#3b82f6', // Default indigo
                    'rrule' => $eventModel->rrule,
                    'recurring' => $eventModel->isRecurring(),
                    'model_id' => $eventModel->id,
                    'model_type' => Event::class,
                ]);
            }
        }

        // Get Task events
        $tasks = Task::query()
            ->whereHas('project', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where(function ($query) use ($start, $end) {
                $query->whereNotNull('start_date')
                    ->where(function ($q) use ($start, $end) {
                        $q->whereBetween('start_date', [$start, $end])
                            ->orWhereBetween('end_date', [$start, $end])
                            ->orWhere(function ($subQ) use ($start, $end) {
                                $subQ->where('start_date', '<=', $start)
                                    ->where(function ($dateQ) use ($end) {
                                        $dateQ->where('end_date', '>=', $end)
                                            ->orWhereNull('end_date');
                                    });
                            });
                    });
            })
            ->get();

        foreach ($tasks as $task) {
            $taskStart = Carbon::parse($task->start_date);
            $taskEnd = $task->end_date ? Carbon::parse($task->end_date) : $taskStart->copy()->endOfDay();

            if ($taskStart->lte($end) && $taskEnd->gte($start)) {
                $events->push([
                    'id' => 'task_'.$task->id,
                    'title' => $task->name,
                    'description' => $task->notes,
                    'start' => $taskStart,
                    'end' => $taskEnd,
                    'type' => 'task',
                    'color' => '#10b981', // Default green
                    'rrule' => null,
                    'recurring' => false,
                    'model_id' => $task->id,
                    'model_type' => Task::class,
                ]);
            }
        }

        // Get Budget period events
        $budgets = Budget::query()
            ->where('user_id', $user->id)
            ->whereNotNull('started_at')
            ->whereNotNull('frequency')
            ->get();

        foreach ($budgets as $budget) {
            // Start from the beginning of the range and work forward
            $cursor = $start->copy();

            // Generate periods until we're past the end date
            $maxIterations = 100; // Safety limit
            $iterations = 0;

            while ($cursor->lte($end) && $iterations < $maxIterations) {
                [$periodStart, $periodEnd] = $this->budgetPeriodHelper->getCurrentPeriod($budget, $cursor);

                // If this period overlaps with our range, add it
                if ($periodStart->lte($end) && $periodEnd->gte($start)) {
                    // Check if we've already added this period
                    $periodId = 'budget_'.$budget->id.'_'.md5($periodStart->toIso8601String());
                    if (! $events->contains('id', $periodId)) {
                        $events->push([
                            'id' => $periodId,
                            'title' => 'Budget: '.$budget->name,
                            'description' => '$'.number_format($budget->amount, 2),
                            'start' => $periodStart,
                            'end' => $periodEnd,
                            'type' => 'budget',
                            'color' => '#8b5cf6', // Default purple
                            'rrule' => null,
                            'recurring' => true,
                            'model_id' => $budget->id,
                            'model_type' => Budget::class,
                        ]);
                    }
                }

                // Move to next period
                $frequency = $budget->getFrequencyEnum();
                $interval = $budget->getIntervalInt();
                $nextStart = match ($frequency) {
                    Budget::FREQUENCY_DAILY => $periodStart->copy()->addDays($interval),
                    Budget::FREQUENCY_WEEKLY => $periodStart->copy()->addWeeks($interval),
                    Budget::FREQUENCY_BIWEEKLY => $periodStart->copy()->addWeeks(2 * $interval),
                    Budget::FREQUENCY_SEMIMONTHLY => $periodStart->copy()->addDays(15 * $interval),
                    Budget::FREQUENCY_MONTHLY => $periodStart->copy()->addMonths($interval),
                    Budget::FREQUENCY_BIMONTHLY => $periodStart->copy()->addMonths(2 * $interval),
                    Budget::FREQUENCY_YEARLY => $periodStart->copy()->addYears($interval),
                    default => $periodStart->copy()->addMonths($interval),
                };

                if ($nextStart->lte($periodStart)) {
                    break; // Prevent infinite loop
                }

                $cursor = $nextStart;
                $iterations++;
            }
        }

        // Get Operation events
        $operations = Operation::query()
            ->whereNotNull('should_run_at')
            ->whereBetween('should_run_at', [$start, $end])
            ->get();

        foreach ($operations as $operation) {
            $runAt = Carbon::parse($operation->should_run_at);
            $events->push([
                'id' => 'operation_'.$operation->id,
                'title' => 'Operation: '.class_basename($operation),
                'description' => 'Scheduled operation',
                'start' => $runAt,
                'end' => $runAt->copy()->addHour(),
                'type' => 'operation',
                'color' => '#f59e0b', // Default amber
                'rrule' => null,
                'recurring' => false,
                'model_id' => $operation->id,
                'model_type' => get_class($operation),
            ]);
        }

        return $events->sortBy('start');
    }
}
