<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ServerServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service' => $this->faker->word(),
            'version' => $this->faker->word(),
            'status' => $this->faker->word(),
            'settings' => [],
        ];
    }
}
