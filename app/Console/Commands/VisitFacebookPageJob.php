<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Dusk\Browser;

class VisitFacebookPageJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:visit-facebook-page-job';

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
        $this->browse(function (Browser $browser) {
            $browser->visit('https://www.facebook.com/newsreview')->assertSee('Facebook');
        });
    }
}
