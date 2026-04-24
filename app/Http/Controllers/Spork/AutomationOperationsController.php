<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Http\Controllers\Controller;
use App\Models\Automation;
use App\Operations\AutomationOperation;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AutomationOperationsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user !== null, 404);

        $automation_ids = $user->automations()->pluck('id')->all();

        $status = (string) $request->query('status', 'all');
        $automation_id = $request->query('automation_id');

        $query = AutomationOperation::query()
            ->with(['automation:id,name,user_id'])
            ->whereIn('automation_id', $automation_ids);

        if ($automation_id !== null && $automation_id !== '' && in_array((int) $automation_id, $automation_ids, true)) {
            $query->where('automation_id', (int) $automation_id);
        }

        match ($status) {
            'queued' => $query->whereNull('started_run_at'),
            'running' => $query->whereNotNull('started_run_at')->whereNull('finished_run_at'),
            'finished' => $query->whereNotNull('finished_run_at')->whereNull('error'),
            'errored' => $query->whereNotNull('error'),
            default => null,
        };

        $operations = $query
            ->latest('should_run_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (AutomationOperation $operation) => $this->serialize($operation));

        $automations = Automation::query()
            ->whereIn('id', $automation_ids)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->all();

        $counts = [
            'all' => count($automation_ids) === 0 ? 0 : AutomationOperation::query()->whereIn('automation_id', $automation_ids)->count(),
            'queued' => AutomationOperation::query()->whereIn('automation_id', $automation_ids)->whereNull('started_run_at')->count(),
            'running' => AutomationOperation::query()->whereIn('automation_id', $automation_ids)->whereNotNull('started_run_at')->whereNull('finished_run_at')->count(),
            'finished' => AutomationOperation::query()->whereIn('automation_id', $automation_ids)->whereNotNull('finished_run_at')->whereNull('error')->count(),
            'errored' => AutomationOperation::query()->whereIn('automation_id', $automation_ids)->whereNotNull('error')->count(),
        ];

        return Inertia::render('Automations/Operations', [
            'title' => 'Automation operations',
            'operations' => $operations,
            'automations' => $automations,
            'filters' => [
                'status' => in_array($status, ['all', 'queued', 'running', 'finished', 'errored'], true) ? $status : 'all',
                'automation_id' => $automation_id !== null && $automation_id !== '' ? (int) $automation_id : null,
            ],
            'counts' => $counts,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(AutomationOperation $operation): array
    {
        $should = $this->toCarbon($operation->getAttribute('should_run_at'));
        $started = $this->toCarbon($operation->getAttribute('started_run_at'));
        $finished = $this->toCarbon($operation->getAttribute('finished_run_at'));

        $status = match (true) {
            $operation->error !== null && $operation->error !== '' => 'errored',
            $finished !== null => 'finished',
            $started !== null => 'running',
            default => 'queued',
        };

        $duration_ms = null;
        if ($started !== null && $finished !== null) {
            $duration_ms = $started->diffInMilliseconds($finished);
        }

        return [
            'id' => $operation->getKey(),
            'automation_id' => $operation->automation_id,
            'automation_name' => $operation->automation?->name,
            'status' => $status,
            'should_run_at' => $should?->toIso8601String(),
            'started_run_at' => $started?->toIso8601String(),
            'finished_run_at' => $finished?->toIso8601String(),
            'duration_ms' => $duration_ms,
            'output_preview' => $this->preview((string) ($operation->output ?? '')),
            'error_preview' => $this->preview((string) ($operation->error ?? '')),
            'has_output' => $operation->output !== null && $operation->output !== '',
            'has_error' => $operation->error !== null && $operation->error !== '',
        ];
    }

    private function toCarbon(mixed $value): ?CarbonInterface
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        return Carbon::parse($value);
    }

    private function preview(string $value, int $length = 240): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (mb_strlen($value) <= $length) {
            return $value;
        }

        return mb_substr($value, 0, $length).'…';
    }
}
