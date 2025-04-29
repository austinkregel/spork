<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Credential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Domain>
 */
class DomainFactory extends Factory
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
            'verification_key' => $this->faker->uuid(),
            'cloudflare_id' => $this->faker->uuid(),
            'credential_id' => Credential::factory(),
            'domain_id' => $this->faker->uuid(),
            'registered_at' => $this->faker->dateTime(),
            'expires_at' => $this->faker->dateTime(),
        ];
    }
}
