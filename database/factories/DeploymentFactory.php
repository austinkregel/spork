<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Domain;
use App\Models\Project;
use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Deployment>
 */
class DeploymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => $this->faker->name(),
            'type' => $this->faker->name(),
            // Domains are required, but we can have multiple domains per deployment as aliases
            'primary_domain_id' => Domain::factory(),
            // The primary server is required and indicates the public entrypoint for the app/project
            'primary_server_id' => Server::factory(),
            // What steps do we take; this basically stores some domain logic
            'deployment_strategies' => json_encode([]),
            'repository_base_url' => $this->faker->url(),
            'repository_status' => $this->faker->name(),
            'user' => $this->faker->name(),
            'maintenance_mode' => $this->faker->boolean(),
            'full_path' => $this->faker->filePath(),
            'executable_version' => $this->faker->name(),
            'executable_binary' => $this->faker->name(),
            'secured' => $this->faker->boolean(),
            'deploy_started_at' => $this->faker->dateTime(),
            'deploy_ended_at' => $this->faker->dateTime(),
            'last_deployed_at' => $this->faker->dateTime(),
            'last_deployed_commit' => $this->faker->name(),
            'deployment_duration' => null,
        ];
    }
}
