<?php

declare(strict_types=1);

namespace Database\Factories\Article;

use App\Models\Article\SocialFeed;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialFeed>
 */
class SocialFeedFactory extends Factory
{
    protected $model = SocialFeed::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->paragraph(),
            'is_public' => false,
            'must_all_conditions_pass' => false,
        ];
    }
}
