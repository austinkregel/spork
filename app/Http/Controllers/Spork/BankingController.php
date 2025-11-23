<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Models\Finance\Transaction;
use App\Services\Finance\BankingDashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BankingController
{
    public function __construct(
        protected BankingDashboardService $dashboardService,
    ) {}

    public function overview(Request $request)
    {
        $range = $request->get('range', 'MTD');
        $user = $request->user();

        return Inertia::render('Banking/Index', [
            'tab' => 'overview',
            'navigation' => $this->dashboardService->navigation('overview'),
            'overview' => $this->dashboardService->buildOverview($user, $range),
        ]);
    }

    public function accounts(Request $request)
    {
        $user = $request->user();
        $preferences = $this->dashboardService->preferencesFor($user);

        return Inertia::render('Banking/Index', [
            'tab' => 'accounts',
            'navigation' => $this->dashboardService->navigation('accounts'),
            'accountsData' => [
                'accounts' => $this->dashboardService->buildAccounts($user, $preferences),
                'preferences' => $preferences,
            ],
        ]);
    }

    public function budgets(Request $request)
    {
        $user = $request->user();
        $preferences = $this->dashboardService->preferencesFor($user);

        return Inertia::render('Banking/Index', [
            'tab' => 'budgets',
            'navigation' => $this->dashboardService->navigation('budgets'),
            'budgetsData' => [
                'budgets' => $this->dashboardService->buildBudgets($user, $preferences),
                'preferences' => $preferences,
            ],
        ]);
    }

    public function transactions(Request $request)
    {
        $user = $request->user();
        $preferences = $this->dashboardService->preferencesFor($user);
        $transactions = $this->queryBuilder($request);

        return Inertia::render('Banking/Index', [
            'tab' => 'transactions',
            'navigation' => $this->dashboardService->navigation('transactions'),
            'transactionsData' => [
                'transactions' => $transactions,
                'filters' => $request->only(['filter', 'range']),
                'accounts' => $this->dashboardService->buildAccounts($user, $preferences),
                'tags' => $this->dashboardService->tagOptions($user),
            ],
        ]);
    }

    public function settings(Request $request)
    {
        $user = $request->user();
        $preferences = $this->dashboardService->preferencesFor($user);

        return Inertia::render('Banking/Index', [
            'tab' => 'settings',
            'navigation' => $this->dashboardService->navigation('settings'),
            'settingsData' => [
                'preferences' => $preferences,
            ],
        ]);
    }

    protected function queryBuilder(Request $request)
    {
        $accounts = $request->user()->accounts()->with('credential')->get();

        return QueryBuilder::for(Transaction::class)
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::callback('tag', function ($builder, $name) {
                    $builder->whereHas('tags', fn ($query) => $query->where('name', 'like', '%'.$name.'%'));
                }),
                AllowedFilter::partial('date'),
                AllowedFilter::callback('range', fn () => null),
            ])
            ->allowedIncludes(['account', 'tags'])
            ->allowedSorts(['name', 'amount', 'date'])
            ->where('name', 'not like', '%transfer%')
            ->whereIn('account_id', $accounts->pluck('account_id'))
            ->with(['tags', 'account'])
            ->orderByDesc('date')
            ->paginate()
            ->withQueryString();
    }
}
