<?php

declare(strict_types=1);

namespace Database\Factories\Spork;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Spork\Script>
 */
class ScriptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'language' => $this->faker->text(),
            'script' => $this->faker->text(),
            'user_id' => User::factory(),
        ];
    }
}
