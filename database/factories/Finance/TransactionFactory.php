<?php

declare(strict_types=1);

namespace Database\Factories\Finance;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Finance\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'amount' => $this->faker->numberBetween(2, 100),
            'account_id' => Str::random(32),
            'date' => $this->faker->date(),
            'pending' => $this->faker->boolean,
            'category_id' => $this->faker->numberBetween(2, 1000),
            'transaction_id' => Str::random(32),
            'transaction_type' => 'depository',
            'pending_transaction_id' => null,
            'data' => '[]',
        ];
    }
}
