<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\User;
use Illuminate\Console\Command;

class MakeUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::create([
            'name' => $this->ask('What is your name?'),
            'email' => $this->ask('What is your email address?'),
            'password' => bcrypt($this->ask('What password would you like to use?')),
        ]);

        $user->ownedTeams()->create([
            'name' => config('app.name'),
            'personal_team' => true,
            'settings' => [],
        ]);
    }
}
