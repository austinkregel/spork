<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Thread>
 */
class ThreadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'thread_id' => $this->faker->uuid(),
            'name' => $this->faker->name(),
            'description' => $this->faker->name(),
            'rules' => $this->faker->name(),
            'topic' => $this->faker->name(),
            'settings' => [],
            'origin_server_ts' => $this->faker->unixTime(),
        ];
    }
}
