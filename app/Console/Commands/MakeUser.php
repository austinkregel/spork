<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Fortify\CreateNewUser;
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
        $action = new CreateNewUser();

        $action->create([
            'name' => $this->ask('What is your name?'),
            'email' => $this->ask('What is your email address?'),
            'password' => $password = $this->ask('What password would you like to use?'),
            'password_confirmation' => $password,
            'terms' => true,
        ]);
    }
}
