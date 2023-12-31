<?php

declare(strict_types=1);

namespace Database\Factories\Finance;

use App\Models\Credential;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Finance\Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'credential_id' => Credential::factory(),
            'name' => $this->faker->name,
            'mask' => $this->faker->randomNumber(4),
            'type' => 'checking',
            'account_id' => Str::random(32),
            'balance' => $this->faker->randomNumber(2),
            'available' => $this->faker->randomNumber(2),
        ];
    }
}
