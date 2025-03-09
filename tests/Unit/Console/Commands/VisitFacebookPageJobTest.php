<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\VisitFacebookPageJob;
use Illuminate\Console\Command;
use Laravel\Dusk\Browser;
use Mockery;
use Tests\TestCase;

class VisitFacebookPageJobTest extends TestCase
{
    public function test_handle_method()
    {
        $command = Mockery::mock(VisitFacebookPageJob::class)->makePartial();
        $browser = Mockery::mock(Browser::class);

        $command->shouldReceive('browse')->once()->with(Mockery::on(function ($closure) use ($browser) {
            $closure($browser);
            return true;
        }));

        $browser->shouldReceive('visit')->once()->with('https://www.facebook.com/newsreview')->andReturnSelf();
        $browser->shouldReceive('assertSee')->once()->with('Facebook');

        $command->handle();
    }
}
