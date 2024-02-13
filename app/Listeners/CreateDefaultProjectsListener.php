<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Models\User\UserCreated;

class CreateDefaultProjectsListener
{
    public const DEFAULT_PROJECT_NAMES = [
        'Work related',
        'Random',
        // Things I tinker with, things I'm exploring, or I'm not sure about.
        'Personal',
        // Things I believe will make money
        'Side Projects',
        // I want to keep my political things separate from the rest of my projects
        'Society & Politics',
        // Things I made with the people I care about.
        'Friends & Partners',
    ];

    public function handle(UserCreated $userEvent)
    {
        $user = $userEvent->model;

        foreach (static::DEFAULT_PROJECT_NAMES as $name) {
            $user->projects()->create([
                'name' => $name,
                'settings' => [],
                'team_id' => $user->teams()->first()->id ?? 1,
            ]);
        }
    }
}
