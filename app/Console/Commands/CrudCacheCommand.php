<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Repositories\TableDescriptionRepository;
use App\Services\Code;
use App\Services\Development\DescribeTable\TableDescriptionFactory;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CrudCacheCommand extends Command
{
    protected $signature = 'crud:cache
        {--model= : Fully-qualified model class or table name (comma-separated)}
        {--flush : Remove existing CRUD cache files}
        {--force : Rebuild even if cache is fresh}';

    protected $description = 'Generate CRUD metadata cache for DescribeTableService.';

    public function __construct(
        private readonly TableDescriptionFactory $factory,
        private readonly TableDescriptionRepository $repository,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('flush')) {
            return $this->flush();
        }

        $targets = $this->resolveTargets();

        if (empty($targets)) {
            $this->warnComponent('No targets discovered.');

            return self::SUCCESS;
        }

        $force = (bool) $this->option('force');
        $migrationHash = $this->repository->migrationHash();
        $processed = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($targets as $target) {
            $table = $target['table'];

            if (! $force && ! $this->repository->needsRefresh($table)) {
                $skipped++;
                $this->line("Skipping {$table}, cache is fresh.");
                continue;
            }

            try {
                $description = $target['type'] === 'model'
                    ? $this->factory->forModel($target['instance'], false)
                    : $this->factory->forTable($table);

                $this->repository->put($description, $migrationHash);
                $processed++;
                $this->infoComponent("Cached {$table}");
            } catch (\Throwable $e) {
                $failed++;
                $this->errorComponent("Failed {$table}: {$e->getMessage()}");
            }
        }

        $this->infoComponent("CRUD cache complete. Cached: {$processed}, Skipped: {$skipped}, Failed: {$failed}");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function flush(): int
    {
        $modelOption = $this->option('model');

        if ($modelOption) {
            foreach ($this->parseListOption($modelOption) as $target) {
                $table = $this->normalizeTableName($target);

                if ($table === '') {
                    continue;
                }

                $this->repository->flush($table);
                $this->infoComponent("Flushed cache for {$table}");
            }
        } else {
            $this->repository->flush();
            $this->infoComponent('Flushed all CRUD cache files');
        }

        return self::SUCCESS;
    }

    private function resolveTargets(): array
    {
        $modelOption = $this->option('model');

        if ($modelOption) {
            return array_values(array_filter(array_map(fn ($item) => $this->buildTargetFromInput($item), $this->parseListOption($modelOption))));
        }

        return $this->discoverModels();
    }

    private function parseListOption(string $option): array
    {
        return array_filter(array_map('trim', explode(',', $option)));
    }

    private function buildTargetFromInput(string $input): ?array
    {
        $candidates = [$input];

        if (! Str::contains($input, '\\')) {
            $candidates[] = 'App\\Models\\'.ltrim($input, '\\');
        }

        foreach ($candidates as $candidate) {
            if (class_exists($candidate) && is_subclass_of($candidate, Model::class)) {
                /** @var Model $model */
                $model = app($candidate);

                return [
                    'type' => 'model',
                    'table' => $model->getTable(),
                    'instance' => $model,
                ];
            }
        }

        $table = $this->normalizeTableName($input);

        if ($table) {
            return [
                'type' => 'table',
                'table' => $table,
            ];
        }

        $this->warnComponent("Unable to resolve {$input} to a model or table.");

        return null;
    }

    private function normalizeTableName(string $input): string
    {
        $input = trim($input);

        if ($input === '') {
            return '';
        }

        $sanitized = $input;

        if (Str::contains($sanitized, '\\')) {
            $sanitized = class_basename($sanitized);
        }

        $sanitized = str_replace('/', '_', $sanitized);

        return Str::snake($sanitized);
    }

    private function discoverModels(): array
    {
        $classes = Code::instancesOf(Model::class)->getClasses();
        $targets = [];

        foreach ($classes as $class) {
            if (! class_exists($class)) {
                continue;
            }

            if (! Str::startsWith($class, 'App\\')) {
                continue;
            }

            $reflection = new \ReflectionClass($class);

            if ($reflection->isAbstract()) {
                continue;
            }

            if (! $reflection->isSubclassOf(Model::class)) {
                continue;
            }

            /** @var Model $instance */
            $instance = app($class);

            $targets[] = [
                'type' => 'model',
                'table' => $instance->getTable(),
                'instance' => $instance,
            ];
        }

        return $targets;
    }

    private function infoComponent(string $message): void
    {
        if (property_exists($this, 'components')) {
            $this->components->info($message);

            return;
        }

        $this->info($message);
    }

    private function warnComponent(string $message): void
    {
        if (property_exists($this, 'components')) {
            $this->components->warn($message);

            return;
        }

        $this->warn($message);
    }

    private function errorComponent(string $message): void
    {
        if (property_exists($this, 'components')) {
            $this->components->error($message);

            return;
        }

        $this->error($message);
    }
}

