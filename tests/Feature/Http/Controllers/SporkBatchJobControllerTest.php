<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkBatchJobControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_jobs_route_is_accessible()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/batch-jobs');

        $response->assertStatus(200);
    }

    public function test_batch_jobs_route_loads_expected_data()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/batch-jobs');

        $response->assertInertia(fn ($page) => $page
            ->component('Admin/BatchJob/Index')
            ->has('paginator')
        );
    }

    public function test_batch_job_show_route_is_accessible()
    {
        $batchJob = \App\Models\JobBatch::factory()->create();

        $response = $this->actingAsUser()->get("http://spork.localhost/-/batch-jobs/$batchJob->id");

        $response->assertStatus(200);
    }

    public function test_batch_job_show_route_loads_expected_data()
    {
        $batchJob = \App\Models\JobBatch::factory()->create();

        $response = $this->actingAsUser()->get("http://spork.localhost/-/batch-jobs/$batchJob->id");

        $response->assertInertia(fn ($page) => $page
            ->component('Admin/BatchJob/Show')
            ->has('paginator')
        );
    }
}
