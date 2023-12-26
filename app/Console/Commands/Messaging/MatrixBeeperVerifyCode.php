<?php

declare(strict_types=1);

namespace App\Console\Commands\Messaging;

use Illuminate\Console\Command;

class MatrixBeeperVerifyCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matrix:beeper-verify-code {email} {code} {--host=beeper.com}';

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
        $client = new \App\Services\Matrix\MatrixClient($this->argument('email'), $this->option('host'));

        $response = $client->loginWithBeeperCode($this->argument('email'), $this->argument('code'));

        $this->info('Login successful');
        dd($response);
    }
}
