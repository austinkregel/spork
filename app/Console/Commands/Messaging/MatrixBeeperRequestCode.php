<?php

namespace App\Console\Commands\Messaging;

use Illuminate\Console\Command;

class MatrixBeeperRequestCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matrix:beeper-request-code {email} {--host=beeper.com}';

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

        $client->requestCodeForBeeper($this->argument('email'));
        $this->info('Please check your email, and return within 30 minutes');
        $this->info('When you\'re ready please run the following command');
        $this->warn('    sail art matrix:beeper-verify-code '.$this->argument('email').' {code}');
    }
}
