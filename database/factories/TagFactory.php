<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tag>
 */
class TagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $word = $this->faker->unique()->word();

        return [
            'name' => [
                'en' => $word,
            ],
            'slug' => [
                'en' => Str::slug($word),
            ],
            'type' => 'automatic',
            'order_column' => 1,
            'must_all_conditions_pass' => true,
        ];
    }
}
