<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Models\Credential;
use App\Models\Finance\PrivacyCard;
use App\Models\Finance\PrivacyTransaction;
use App\Models\Finance\Transaction;
use App\Services\Finance\BankingDashboardService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                'tags' => $this->dashboardService->tagOptions($user),
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

    public function privacy(Request $request)
    {
        $user = $request->user();

        // Credentials are treated as "team-owned" in other parts of the system (e.g. uniqueness checks),
        // so we mirror that here: any user sharing a team with the current user is considered a valid owner.
        $teamIds = DB::table('team_user')
            ->where('user_id', $user->id)
            ->pluck('team_id')
            ->merge(
                DB::table('teams')
                    ->where('user_id', $user->id)
                    ->pluck('id')
            )
            ->unique()
            ->values();

        $ownerUserIds = $teamIds->isEmpty()
            ? collect([$user->id])
            : DB::table('team_user')
                ->whereIn('team_id', $teamIds)
                ->pluck('user_id')
                ->merge(
                    DB::table('teams')
                        ->whereIn('id', $teamIds)
                        ->pluck('user_id')
                )
                ->unique()
                ->values();

        $privacyCredentialIds = Credential::query()
            ->whereIn('user_id', $ownerUserIds)
            ->where('type', Credential::TYPE_FINANCE)
            ->where('service', Credential::PRIVACY)
            ->pluck('id')
            ->all();

        $since30Days = CarbonImmutable::now('UTC')->subDays(30);

        $openCardsQuery = PrivacyCard::query()
            ->whereIn('credential_id', $privacyCredentialIds)
            ->where(function ($query) {
                $query->whereNull('state')->orWhere('state', '!=', 'CLOSED');
            });

        $openCardsCount = (clone $openCardsQuery)->count();

        // Recently used cards: order open cards by latest Privacy transaction activity.
        $recentCardActivity = PrivacyTransaction::query()
            ->selectRaw('card_uuid, MAX(date_authorized) as last_used_at')
            ->whereIn('credential_id', $privacyCredentialIds)
            ->whereNotNull('card_uuid')
            ->groupBy('card_uuid');

        $recentCards = (clone $openCardsQuery)
            ->select([
                'privacy_cards.id',
                'privacy_cards.credential_id',
                'privacy_cards.card_token',
                'privacy_cards.state',
                'privacy_cards.type',
                'privacy_cards.memo',
                'privacy_cards.descriptor',
                'privacy_cards.spend_limit_cents',
                'privacy_cards.spend_limit_duration',
            ])
            ->leftJoinSub($recentCardActivity, 'privacy_card_activity', function ($join) {
                $join->on('privacy_cards.card_token', '=', 'privacy_card_activity.card_uuid');
            })
            ->addSelect('privacy_card_activity.last_used_at')
            ->orderByDesc('privacy_card_activity.last_used_at')
            ->orderByDesc('privacy_cards.updated_at')
            ->limit(5)
            ->get()
            ->map(fn (PrivacyCard $card) => [
                'id' => $card->id,
                'credential_id' => $card->credential_id,
                'card_token' => $card->card_token,
                'state' => $card->state,
                'type' => $card->type,
                'memo' => $card->memo,
                'descriptor' => $card->descriptor,
                'spend_limit_cents' => $card->spend_limit_cents,
                'spend_limit_duration' => $card->spend_limit_duration,
                'last_used_at' => $card->getAttribute('last_used_at'),
            ])
            ->all();

        $transactions = PrivacyTransaction::query()
            ->select([
                'id',
                'credential_id',
                'privacy_transaction_id',
                'result',
                'status',
                'amount_cents',
                'currency_code',
                'date_authorized',
                'date_settled',
                'descriptor',
                'memo',
                'mcc',
                'card_uuid',
                'card_id',
                'created_at',
            ])
            ->whereIn('credential_id', $privacyCredentialIds)
            ->with('tags')
            ->orderByDesc('date_authorized')
            ->paginate(50)
            ->withQueryString();

        $transactionsLast30 = PrivacyTransaction::query()
            ->whereIn('credential_id', $privacyCredentialIds)
            ->where('date_authorized', '>=', $since30Days);

        $summary = [
            'open_cards_count' => $openCardsCount,
            'transactions_30d_count' => (clone $transactionsLast30)->count(),
            'pending_30d_count' => (clone $transactionsLast30)->where('status', 'PENDING')->count(),
            'declined_30d_count' => (clone $transactionsLast30)->where('result', 'DECLINED')->count(),
            'settled_approved_30d_sum_cents' => (int) (clone $transactionsLast30)
                ->where('status', 'SETTLED')
                ->where('result', 'APPROVED')
                ->sum('amount_cents'),
        ];

        return Inertia::render('Banking/Index', [
            'tab' => 'privacy',
            'navigation' => $this->dashboardService->navigation('privacy'),
            'privacyData' => [
                'recent_cards' => $recentCards,
                'transactions' => $transactions,
                'summary' => $summary,
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
            ->allowedIncludes(['account', 'tags', 'privacyTransactions'])
            ->allowedSorts(['name', 'amount', 'date'])
            ->where('name', 'not like', '%transfer%')
            ->whereIn('account_id', $accounts->pluck('account_id'))
            ->with(['tags', 'account', 'privacyTransactions.tags'])
            ->orderByDesc('date')
            ->paginate()
            ->withQueryString();
    }
}
