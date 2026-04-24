<?php

declare(strict_types=1);

namespace App\Console\Commands\Navigation;

use App\Contracts\Navigation\Pillars\PillarSubNavProviderContract;
use App\Services\Programming\LaravelProgrammingStyle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class CacheNavigationCommand extends Command
{
    protected $signature = 'navigation:cache';

    protected $description = 'Cache pillar navigation provider class names for fast registry bootstrap';

    public function handle(): int
    {
        $discovered = LaravelProgrammingStyle::instancesOf(PillarSubNavProviderContract::class)
            ->getClasses();

        /** @var list<class-string<PillarSubNavProviderContract>> $classes */
        $classes = $discovered !== [] ? $discovered : [
            \App\Navigation\Pillars\FinancePillar::class,
            \App\Navigation\Pillars\CommunicationPillar::class,
            \App\Navigation\Pillars\FeedsPillar::class,
            \App\Navigation\Pillars\ProjectsPillar::class,
            \App\Navigation\Pillars\AutomationsPillar::class,
            \App\Navigation\Pillars\InfrastructurePillar::class,
        ];

        $path = base_path('bootstrap/cache/navigation.php');
        File::ensureDirectoryExists(dirname($path));

        $export = var_export(['providers' => $classes], true);
        File::put($path, "<?php\n\nreturn {$export};\n");

        $this->info('Wrote '.$path.' with '.count($classes).' providers.');

        return self::SUCCESS;
    }
}
