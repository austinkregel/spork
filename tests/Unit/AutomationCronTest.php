<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Console\Commands\Automations\SeedNextRuns;
use App\Models\Automation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AutomationCronTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_next_runs_creates_future_operation()
    {
        $this->actingAsUser();
        $automation = Automation::create([
            'user_id' => $this->user->id,
            'name' => 'Cron Test',
            'enabled' => true,
            'cron_expression' => '*/10 * * * *',
        ]);

        Artisan::call(SeedNextRuns::class);

        $this->assertDatabaseHas('automation_operations', [
            'automation_id' => $automation->id,
        ]);
    }
}


