<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MessageReaction>
 */
class MessageReactionFactory extends Factory
{
    protected $model = MessageReaction::class;

    public function definition(): array
    {
        return [
            'message_id' => Message::factory(),
            'person_id' => Person::factory(),
            'sender_identifier' => '@'.$this->faker->userName().':matrix.test',
            'emoji' => $this->faker->randomElement(['😀', '😂', '😍', '👍', '🎉']),
            'matrix_event_id' => '$'.Str::uuid()->toString(),
            'payload' => [],
        ];
    }
}

