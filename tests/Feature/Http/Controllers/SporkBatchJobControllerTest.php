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
        $batchJob = \App\Models\JobBatch::factory()->create([
            'total_jobs' => 5,
            'pending_jobs' => 3,
            'failed_jobs' => 1,
            'failed_job_ids' => json_encode([1]),
            'id' => fake()->uuid()
        ]);

        $this->assertDatabaseCount('job_batches', 1);
        $this->assertDatabaseHas('job_batches', [
            'id' => $batchJob->id,
        ]);
        $response = $this->actingAsUser()->get("http://spork.localhost/-/batch-jobs/$batchJob->id");

        $response->assertStatus(200);
    }

    public function test_batch_job_show_route_loads_expected_data()
    {
        $batchJob = \App\Models\JobBatch::factory()->create();
        $this->assertDatabaseCount('job_batches', 1);
        $this->assertDatabaseHas('job_batches', [
            'id' => $batchJob->id,
        ]);
        $response = $this->actingAsUser()->get("http://spork.localhost/-/batch-jobs/$batchJob->id");

        $response->assertInertia(fn ($page) => $page
            ->component('Admin/BatchJob/Show')
            ->has('paginator')
        );
    }
}
