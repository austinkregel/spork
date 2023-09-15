<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Credential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Server>
 */
class ServerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'server_id' => $this->faker->randomNumber(),
            'credential_id' => Credential::factory(),
            'name' => $this->faker->name(),
            'vcpu' => $this->faker->randomNumber(),
            'memory' => $this->faker->randomNumber(),
            'disk' => $this->faker->randomNumber(),
            'cost_per_hour' => $this->faker->randomNumber(),
            'ip_address' => '127.0.0.1',
            'ip_address_v6' => '',
            'internal_ip_address' => '127.0.0.1',
            'internal_ip_address_v6' => '',
            'internal_url' => 'localhost',
            'last_ping_at' => null,
            'booted_at' => null,
            'turned_off_at' => null,
            'os' => 'Ubuntu',
        ];
    }
}
