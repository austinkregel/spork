<?php

declare(strict_types=1);

namespace Database\Factories\Finance;

use App\Models\Finance\Budget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Finance\Budget>
 */
class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'amount' => $this->faker->numberBetween(100, 1000),
            'user_id' => User::factory(),
            'started_at' => Carbon::now()->subDays($this->faker->numberBetween(1, 30)),
            'frequency' => $this->faker->randomElement(['daily', 'weekly', 'monthly']),
            'interval' => $this->faker->numberBetween(1, 12),
            'count' => $this->faker->numberBetween(1, 10),
            'breached_at' => null,
        ];
    }
}
