<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Credential;
use App\Models\Person;
use App\Models\Thread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'from_person' => Person::factory(),
            'to_person' => Person::factory(),
            'thread_id' => Thread::factory(),
            'type' => $this->faker->randomElement(['email', 'sms', 'notification']),
            'event_id' => $this->faker->uuid(),
            'originated_at' => $this->faker->dateTime(),
            'thumbnail_url' => $this->faker->imageUrl(),
            'is_decrypted' => $this->faker->boolean(),
            'message' => $this->faker->text(),
            'html_message' => $this->faker->randomHtml(),
            'settings' => $this->faker->randomElements(),
            'credential_id' => Credential::factory(),
        ];
    }
}
