<?php

declare(strict_types=1);

namespace App\Console\Commands\Messaging;

use Illuminate\Console\Command;

class MatrixBeeperRequestTokenCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matrix:beeper-r-code {email} {code} {--host=beeper.com}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $client = new \App\Services\Messaging\MatrixClient($this->argument('email'), $this->option('host'));

        $response = $client->loginWithJwt($this->argument('code'));

        $this->info('Login successful');
        dd($response);
    }
}
