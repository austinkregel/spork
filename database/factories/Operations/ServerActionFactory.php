<?php

declare(strict_types=1);

namespace Database\Factories\Operations;

use App\Models\Credential;
use App\Models\Server;
use App\Models\Spork\Script;
use App\Models\User;
use App\Operations\ServerAction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Server>
 */
class ServerActionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'script_id' => Script::factory(),
            'user_id' => User::factory(),
            'server_id' => Server::factory(),
            'credential_id' => Credential::factory(),
            'output' => $this->faker->text(),
            'error' => $this->faker->text(),
            'should_run_at' => now(),
            'started_run_at' => now(),
            'finished_run_at' => now(),
        ];
    }

    public function newModel(array $attributes = [])
    {
        return new ServerAction($attributes);
    }
}
