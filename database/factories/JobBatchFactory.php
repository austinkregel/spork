<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\JobBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'total_jobs' => 0,
            'pending_jobs' => 0,
            'failed_jobs' => 0,
            'failed_job_ids' => json_encode([]),
            'options' => json_encode([]),
            'cancelled_at' => null,
            'finished_at' => null,
            'created_at' => $this->faker->unixTime(),
        ];
    }
}
