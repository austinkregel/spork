<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = \Carbon\Carbon::now()->addDays($this->faker->numberBetween(1, 30));

        return [
            'project_id' => \App\Models\Project::factory(),
            'name' => $this->faker->sentence(3),
            'type' => $this->faker->randomElement(['task', 'bug', 'feature']),
            'status' => $this->faker->randomElement(['todo', 'in_progress', 'done']),
            'checklist' => null,
            'notes' => $this->faker->optional()->paragraph(),
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addDays($this->faker->numberBetween(1, 7)),
            'service_identifier' => null,
        ];
    }
}
