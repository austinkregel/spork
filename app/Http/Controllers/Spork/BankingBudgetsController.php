<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Http\Requests\Banking\DeleteBudgetRequest;
use App\Http\Requests\Banking\StoreBudgetRequest;
use App\Http\Requests\Banking\UpdateBudgetRequest;
use App\Models\Finance\Budget;
use Illuminate\Http\RedirectResponse;

class BankingBudgetsController
{
    public function store(StoreBudgetRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        /** @var Budget $budget */
        $budget = $request->user()->budgets()->create([
            'name' => $payload['name'],
            'amount' => $payload['amount'],
            'frequency' => $payload['frequency'],
            'interval' => $payload['interval'] ?? 1,
            'started_at' => $payload['started_at'] ?? now('UTC')->startOfMonth(),
            'count' => $payload['count'] ?? null,
        ]);

        $budget->tags()->sync($payload['tag_ids'] ?? []);

        return back()->with('flash.banner', 'Budget created.');
    }

    public function update(UpdateBudgetRequest $request, Budget $budget): RedirectResponse
    {
        abort_unless($budget->user_id === $request->user()?->id, 404);

        $payload = $request->validated();

        $budget->update([
            'name' => $payload['name'],
            'amount' => $payload['amount'],
            'frequency' => $payload['frequency'],
            'interval' => $payload['interval'] ?? 1,
            'started_at' => $payload['started_at'] ?? $budget->started_at,
            'count' => $payload['count'] ?? null,
        ]);

        $budget->tags()->sync($payload['tag_ids'] ?? []);

        return back()->with('flash.banner', 'Budget updated.');
    }

    public function destroy(DeleteBudgetRequest $request, Budget $budget): RedirectResponse
    {
        abort_unless($budget->user_id === $request->user()?->id, 404);

        $budget->delete();

        return back()->with('flash.banner', 'Budget deleted.');
    }
}
