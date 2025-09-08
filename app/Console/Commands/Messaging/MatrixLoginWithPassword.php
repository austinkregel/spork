<?php

declare(strict_types=1);

namespace App\Console\Commands\Messaging;

use App\Models\Credential;
use App\Models\User;
use Illuminate\Console\Command;

class MatrixLoginWithPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matrix:login-with-password {username} {--host=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(Credential $credential): void
    {
        $client = new \App\Services\Messaging\MatrixClient($this->option('host'));

        $response = $client->loginWithPassword($this->argument('username'), $this->secret('What is the password?'));

        User::first()->credentials()->create([
            'type' => Credential::TYPE_MATRIX,
            'service' => $this->option('host') ?: 'https://matrix.org',
            'name' => '@' . $this->argument('username').':'.parse_url($this->option('host'), PHP_URL_HOST),
            'access_token' => $response['access_token'],
            'settings' => [
                'device_id' => $response['device_id'],
                'home_server' => $response['home_server'],
                'matrix_server' => $response['well_known']['m.homeserver']['base_url'],
            ],
        ]);
    }
}
