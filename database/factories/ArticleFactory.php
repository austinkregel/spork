<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExternalRssFeed;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
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
            'content' => $this->faker->paragraph(),
            'headline' => $this->faker->sentence(),
            'author_id' => ExternalRssFeed::factory(),
            'author_type' => ExternalRssFeed::class,
            'attachment' => $this->faker->imageUrl(),
            'url' => $this->faker->url(),
        ];
    }
}
