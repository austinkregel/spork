<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Credential;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Credential>
 */
class CredentialFactory extends Factory
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
            'user_id' => User::factory(),
            'api_key' => $this->faker->text(),
            'secret_key' => $this->faker->text(),
            'access_token' => $this->faker->text(),
            'refresh_token' => $this->faker->text(),
            'service' => Credential::TYPE_SSH,
            'type' => Credential::TYPE_SSH,
            'settings' => [],
        ];
    }
}
