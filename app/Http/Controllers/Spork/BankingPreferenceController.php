<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Http\Requests\Banking\UpdateBankingPinsRequest;
use App\Http\Requests\Banking\UpdateBankingSettingsRequest;
use App\Services\Finance\BankingDashboardService;
use Illuminate\Http\RedirectResponse;

class BankingPreferenceController
{
    public function __construct(
        protected BankingDashboardService $dashboardService,
    ) {}

    public function updatePins(UpdateBankingPinsRequest $request): RedirectResponse
    {
        $preferences = $this->dashboardService->preferencesFor($request->user());
        $order = array_values($request->input('order', []));

        if ($request->input('type') === 'accounts') {
            $preferences->pinned_accounts = $order;
        } else {
            $preferences->pinned_budgets = array_map('intval', $order);
        }

        $preferences->save();

        return back()->with('flash.banner', 'Pins updated.');
    }

    public function updateSettings(UpdateBankingSettingsRequest $request): RedirectResponse
    {
        $preferences = $this->dashboardService->preferencesFor($request->user());

        $preferences->settings = array_merge($preferences->settings ?? [], $request->input('settings', []));
        $preferences->save();

        return back()->with('flash.banner', 'Preferences saved.');
    }
}
