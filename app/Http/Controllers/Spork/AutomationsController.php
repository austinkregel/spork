<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Http\Controllers\Controller;
use App\Models\Automation;
use App\Models\AutomationStep;
use App\Operations\AutomationOperation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AutomationsController extends Controller
{
    public function index(Request $request): Response
    {
        $automations = $request->user()->automations()
            ->withCount(['steps'])
            ->latest('updated_at')
            ->paginate(15);

        return Inertia::render('Automation/List', [
            'title' => 'Automations',
            'automations' => $automations,
            'subnavigation' => $this->navigation(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Automation/Create', [
            'title' => 'Create Automation',
            'subnavigation' => $this->navigation(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'enabled' => ['boolean'],
            'cron_expression' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'pacing_per_host_ms' => ['nullable', 'integer', 'min:0'],
            'max_concurrency' => ['nullable', 'integer', 'min:1'],
            'tags' => ['array'],
            'tags.*' => ['integer', 'exists:tags,id'],
            'steps' => ['array'],
            'steps.*.order' => ['required', 'integer', 'min:0'],
            'steps.*.type' => ['required', 'string'],
            'steps.*.config' => ['array'],
        ]);

        $automation = null;
        DB::transaction(function () use (&$automation, $request, $data): void {
            $automation = Automation::create(array_merge($data, [
                'user_id' => $request->user()->getKey(),
            ]));

            if (! empty($data['tags'])) {
                $owned = $request->user()->tags()->pluck('id')->all();
                $attach = array_values(array_intersect($owned, $data['tags']));
                if (! empty($attach)) {
                    $automation->tags()->sync($attach);
                }
            }

            if (! empty($data['steps'])) {
                foreach ($data['steps'] as $step) {
                    $automation->steps()->create($step);
                }
            }
        });

        return redirect()->route('automation.automations.show', $automation);
    }

    public function show(Automation $automation): Response
    {
        Gate::authorize('view', $automation);

        $automation->load(['steps' => fn ($q) => $q->orderBy('order')]);

        return Inertia::render('Automation/Show', [
            'title' => $automation->name,
            'automation' => $automation,
            'steps' => $automation->steps,
            'subnavigation' => $this->navigation(),
        ]);
    }

    public function edit(Automation $automation): Response
    {
        Gate::authorize('update', $automation);

        $automation->load('steps');

        return Inertia::render('Automation/Edit', [
            'title' => 'Edit Automation',
            'automation' => $automation,
            'steps' => $automation->steps,
            'subnavigation' => $this->navigation(),
        ]);
    }

    public function update(Request $request, Automation $automation): RedirectResponse
    {
        Gate::authorize('update', $automation);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'enabled' => ['boolean'],
            'cron_expression' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'pacing_per_host_ms' => ['nullable', 'integer', 'min:0'],
            'max_concurrency' => ['nullable', 'integer', 'min:1'],
            'tags' => ['array'],
            'tags.*' => ['integer', 'exists:tags,id'],
        ]);

        $automation->update($data);

        if ($request->has('tags')) {
            $owned = $request->user()->tags()->pluck('id')->all();
            $attach = array_values(array_intersect($owned, $data['tags'] ?? []));
            $automation->tags()->sync($attach);
        }

        return redirect()->route('automation.automations.show', $automation);
    }

    public function destroy(Automation $automation): RedirectResponse
    {
        Gate::authorize('delete', $automation);
        $automation->delete();

        return redirect()->route('automation.automations.index');
    }

    public function storeStep(Request $request, Automation $automation): RedirectResponse
    {
        Gate::authorize('update', $automation);
        $data = $request->validate([
            'order' => ['required', 'integer', 'min:0'],
            'type' => ['required', 'string'],
            'config' => ['array'],
        ]);

        $automation->steps()->create($data);

        return redirect()->route('automation.automations.edit', $automation);
    }

    public function updateStep(Request $request, Automation $automation, AutomationStep $step): RedirectResponse
    {
        Gate::authorize('update', $automation);
        abort_unless($step->automation_id === $automation->getKey(), 404);

        $data = $request->validate([
            'order' => ['sometimes', 'integer', 'min:0'],
            'type' => ['sometimes', 'string'],
            'config' => ['array'],
        ]);

        $step->update($data);

        return redirect()->route('automation.automations.edit', $automation);
    }

    public function destroyStep(Automation $automation, AutomationStep $step): RedirectResponse
    {
        Gate::authorize('update', $automation);
        abort_unless($step->automation_id === $automation->getKey(), 404);

        $step->delete();

        return redirect()->route('automation.automations.edit', $automation);
    }

    public function runNow(Automation $automation): RedirectResponse
    {
        Gate::authorize('run', $automation);

        AutomationOperation::create([
            'automation_id' => $automation->getKey(),
            'should_run_at' => now(),
        ]);

        return redirect()->route('automation.automations.show', $automation);
    }

    protected function navigation(): Collection
    {
        return Collection::make([
            [
                'name' => 'Overview',
                'href' => '/-/automation',
                'icon' => 'Cog8ToothIcon',
                'slug' => 'overview',
            ],
            [
                'name' => 'Automations',
                'href' => '/-/automation/automations',
                'icon' => 'BoltIcon',
                'slug' => 'automations',
            ],
            [
                'name' => 'Tags + routing',
                'href' => '/-/automation/tags',
                'icon' => 'TagIcon',
                'slug' => 'tags',
            ],
            [
                'name' => 'Playbooks (planned)',
                'href' => '/-/automation#playbooks',
                'icon' => 'DocumentTextIcon',
                'slug' => 'playbooks',
            ],
            [
                'name' => 'Schedules (planned)',
                'href' => '/-/automation#scheduling',
                'icon' => 'CalendarDaysIcon',
                'slug' => 'scheduling',
            ],
        ]);
    }
}
