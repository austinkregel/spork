<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExternalRssFeed>
 */
class ExternalRssFeedFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'url' => $this->faker->url(),
            'name' => $this->faker->name(),
            'profile_photo_path' => $this->faker->imageUrl(),
            'owner_id' => User::factory(),
            'owner_type' => User::class,
        ];
    }
}
