<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Automation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomationsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_loads()
    {
        $this->actingAsUser();

        $response = $this->get('http://spork.localhost/-/automation/automations');
        $response->assertStatus(200);
    }

    public function test_create_store_and_show()
    {
        $this->actingAsUser();

        $payload = [
            'name' => 'My Auto',
            'enabled' => true,
            'cron_expression' => '*/5 * * * *',
            'steps' => [
                ['order' => 0, 'type' => 'dusk', 'config' => ['action' => 'visit', 'url' => 'https://example.com']],
            ],
        ];

        $response = $this->post('http://spork.localhost/-/automation/automations', $payload);
        $response->assertRedirect();

        $automation = Automation::first();
        $this->assertNotNull($automation);

        $show = $this->get('http://spork.localhost/-/automation/automations/'.$automation->id);
        $show->assertStatus(200);
    }

    public function test_run_now_creates_operation()
    {
        $this->actingAsUser();
        $automation = Automation::create([
            'user_id' => $this->user->id,
            'name' => 'RunNow Auto',
            'enabled' => true,
            'cron_expression' => '*/10 * * * *',
        ]);

        $response = $this->post('http://spork.localhost/-/automation/automations/'.$automation->id.'/run-now');
        $response->assertRedirect();

        $this->assertDatabaseHas('automation_operations', [
            'automation_id' => $automation->id,
        ]);
    }
}
