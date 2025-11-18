<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Finance;

use App\Models\Finance\Budget;
use App\Services\Finance\BudgetPeriodHelper;
use Carbon\Carbon;
use Tests\TestCase;

class BudgetPeriodHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2024-01-15 12:00:00');
    }

    public function test_monthly_budget_current_period_is_computed_from_started_at(): void
    {
        $budget = new Budget([
            'name' => 'Monthly Budget',
            'amount' => 1000,
            'frequency' => Budget::FREQUENCY_MONTHLY,
            'interval' => 1,
            'started_at' => Carbon::create(2024, 1, 1, 0, 0, 0, 'UTC'),
        ]);

        $helper = new BudgetPeriodHelper();

        [$start, $end] = $helper->getCurrentPeriod($budget, Carbon::now('UTC'));

        $this->assertTrue($start->equalTo(Carbon::create(2024, 1, 1, 0, 0, 0, 'UTC')));
        $this->assertTrue($end->equalTo(Carbon::create(2024, 2, 1, 0, 0, 0, 'UTC')));
    }

    public function test_weekly_budget_uses_7_day_windows(): void
    {
        $budget = new Budget([
            'name' => 'Weekly Budget',
            'amount' => 100,
            'frequency' => Budget::FREQUENCY_WEEKLY,
            'interval' => 1,
            'started_at' => Carbon::create(2024, 1, 8, 0, 0, 0, 'UTC'),
        ]);

        $helper = new BudgetPeriodHelper();

        // Pick a moment strictly inside the [2024-01-08, 2024-01-15) window.
        [$start, $end] = $helper->getCurrentPeriod($budget, Carbon::create(2024, 1, 14, 12, 0, 0, 'UTC'));

        $this->assertTrue($start->equalTo(Carbon::create(2024, 1, 8, 0, 0, 0, 'UTC')));
        $this->assertTrue($end->equalTo(Carbon::create(2024, 1, 15, 0, 0, 0, 'UTC')));
    }
}


