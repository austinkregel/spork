<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Credential;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Email>
 */
class EmailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seen' => $this->faker->boolean(),
            'spam' => $this->faker->boolean(),
            'answered' => $this->faker->boolean(),
            'subject' => $this->faker->sentence(),
            'message' => $this->faker->sentence(),
            'email_id' => $this->faker->uuid(),
            'credential_id' => Credential::factory(),
            'to' => Person::factory(),
            'to_email' => $this->faker->email(),
            'from' => Person::factory(),
            'from_email' => $this->faker->email(),
            'sent_at' => $this->faker->dateTime(),

        ];
    }
}
