<?php
declare(strict_types=1);

namespace App\Listeners;

use App\Events\Models\User\UserCreated;
use App\Models\Person;

class CreatePersonForNewUserListener
{
    public function handle(UserCreated $userEvent)
    {
        $user = $userEvent->model;
        Person::create([
            'name' => $user->name,
            'emails' => [$user->email],
            'names' => [$user->name],
        ]);
    }
}
