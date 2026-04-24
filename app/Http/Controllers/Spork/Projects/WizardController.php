<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\ProjectMembershipRole;
use App\Models\User;
use App\Projects\ProjectTemplates;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;

class WizardController extends Controller
{
    private const SESSION_KEY = 'projects.wizard';

    /**
     * Ordered list of wizard steps. Order matters for next/previous and progress UI.
     *
     * @var list<string>
     */
    private const STEPS = ['template', 'identity', 'members', 'done'];

    public function start(): RedirectResponse
    {
        Session::forget(self::SESSION_KEY);

        return redirect()->route('projects.wizard.show', ['step' => 'template']);
    }

    public function show(Request $request, string $step, ProjectTemplates $templates): Response|RedirectResponse
    {
        $step = $this->normalizeStep($step);
        $state = $this->state();

        if (! $this->canEnterStep($step, $state)) {
            return redirect()->route('projects.wizard.show', ['step' => $this->firstIncompleteStep($state)]);
        }

        return Inertia::render('Projects/Wizard/Step'.ucfirst($step), [
            'step' => $step,
            'steps' => $this->stepDescriptors($state),
            'state' => $state,
            'templates' => $step === 'template' ? $templates->forFrontend() : null,
            'project' => $step === 'done' && isset($state['project_id'])
                ? Project::query()->find($state['project_id'])?->only(['id', 'name'])
                : null,
        ]);
    }

    public function store(Request $request, string $step, ProjectTemplates $templates, ConnectionInterface $db): RedirectResponse
    {
        $step = $this->normalizeStep($step);
        $state = $this->state();

        return match ($step) {
            'template' => $this->storeTemplate($request, $state, $templates),
            'identity' => $this->storeIdentity($request, $state),
            'members' => $this->storeMembers($request, $state, $db),
            default => redirect()->route('projects.wizard.show', ['step' => $step]),
        };
    }

    private function storeTemplate(Request $request, array $state, ProjectTemplates $templates): RedirectResponse
    {
        $known = collect($templates->forFrontend())->pluck('key')->all();

        $data = $request->validate([
            'template' => ['required', 'string', 'in:'.implode(',', $known)],
        ]);

        $state['template'] = $templates->normalizeKey($data['template']);
        $this->persist($state);

        return redirect()->route('projects.wizard.show', ['step' => 'identity']);
    }

    private function storeIdentity(Request $request, array $state): RedirectResponse
    {
        if (empty($state['template'])) {
            return redirect()->route('projects.wizard.show', ['step' => 'template']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'goal' => ['nullable', 'string', 'max:2000'],
        ]);

        $state['name'] = $data['name'];
        $state['goal'] = $data['goal'] ?? null;
        $this->persist($state);

        return redirect()->route('projects.wizard.show', ['step' => 'members']);
    }

    private function storeMembers(Request $request, array $state, ConnectionInterface $db): RedirectResponse
    {
        if (empty($state['template']) || empty($state['name'])) {
            return redirect()->route('projects.wizard.show', ['step' => empty($state['template']) ? 'template' : 'identity']);
        }

        $data = $request->validate([
            'members' => ['nullable', 'array'],
            'members.*.email' => ['required', 'email', 'max:255'],
            'members.*.role' => ['required', 'string', 'in:'.implode(',', ProjectMembershipRole::values())],
        ]);

        $members = $data['members'] ?? [];

        /** @var Project|null $project */
        $project = null;

        $db->transaction(function () use (&$project, $state, $members, $request): void {
            $project = new Project;
            $project->forceFill([
                'name' => $state['name'],
                'user_id' => $request->user()?->getKey(),
                'settings' => [
                    'template' => $state['template'],
                    'goal' => $state['goal'] ?? null,
                ],
            ]);
            $project->save();

            foreach ($members as $member) {
                $invitee = User::query()->where('email', $member['email'])->first();
                if ($invitee === null || $project->isOwnedBy($invitee)) {
                    continue;
                }

                ProjectMembership::query()->updateOrCreate(
                    [
                        'project_id' => $project->getKey(),
                        'user_id' => $invitee->getKey(),
                    ],
                    [
                        'role' => ProjectMembershipRole::from($member['role']),
                        'invited_at' => now(),
                    ],
                );
            }
        });

        $state['project_id'] = $project?->getKey();
        $state['completed'] = true;
        $this->persist($state);

        return redirect()->route('projects.wizard.show', ['step' => 'done']);
    }

    public function reset(): RedirectResponse
    {
        Session::forget(self::SESSION_KEY);

        return redirect()->route('projects.wizard.show', ['step' => 'template']);
    }

    private function state(): array
    {
        $value = Session::get(self::SESSION_KEY, []);

        return is_array($value) ? $value : [];
    }

    private function persist(array $state): void
    {
        Session::put(self::SESSION_KEY, $state);
    }

    private function normalizeStep(string $step): string
    {
        return in_array($step, self::STEPS, true) ? $step : self::STEPS[0];
    }

    private function canEnterStep(string $step, array $state): bool
    {
        return match ($step) {
            'template' => true,
            'identity' => ! empty($state['template']),
            'members' => ! empty($state['template']) && ! empty($state['name']),
            'done' => ! empty($state['project_id']),
            default => false,
        };
    }

    private function firstIncompleteStep(array $state): string
    {
        if (empty($state['template'])) {
            return 'template';
        }
        if (empty($state['name'])) {
            return 'identity';
        }
        if (empty($state['project_id'])) {
            return 'members';
        }

        return 'done';
    }

    /**
     * @return list<array{key: string, label: string, completed: bool, current: bool}>
     */
    private function stepDescriptors(array $state): array
    {
        $labels = [
            'template' => 'Template',
            'identity' => 'Details',
            'members' => 'Members',
            'done' => 'Done',
        ];

        $completed = [
            'template' => ! empty($state['template']),
            'identity' => ! empty($state['name']),
            'members' => ! empty($state['project_id']),
            'done' => ! empty($state['completed']),
        ];

        return array_map(static fn (string $key) => [
            'key' => $key,
            'label' => $labels[$key],
            'completed' => $completed[$key],
        ], self::STEPS);
    }
}
