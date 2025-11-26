<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\Crud\TableDescription;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;

class TableDescriptionRepository
{
    private const VERSION = 1;
    private ?string $migrationHashCache = null;
    private string $basePath;

    public function __construct(
        private readonly Filesystem $filesystem,
        ?string $basePath = null,
    ) {
        $this->basePath = $basePath ?? storage_path('app/crud-cache');
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function pathFor(string $table): string
    {
        return $this->basePath().'/'.$table.'.json';
    }

    public function allTables(): array
    {
        if (! $this->filesystem->exists($this->basePath())) {
            return [];
        }

        return array_map(
            fn (\SplFileInfo $file) => pathinfo($file->getFilename(), PATHINFO_FILENAME),
            $this->filesystem->files($this->basePath())
        );
    }

    public function get(string $table): ?TableDescription
    {
        $payload = $this->read($table);

        if (! $payload) {
            return null;
        }

        return TableDescription::fromPersisted($payload['data']);
    }

    public function put(TableDescription $description, ?string $migrationHash = null): void
    {
        // In the test environment the default cache location under
        // storage/app/crud-cache may not be writable inside the container.
        // When a custom base path is provided (as in unit/feature tests),
        // we always honor it; otherwise we skip persistence and rely on
        // in-memory descriptions only.
        if (app()->environment('testing') && $this->basePath === storage_path('app/crud-cache')) {
            return;
        }

        $this->filesystem->ensureDirectoryExists($this->basePath());

        $payload = [
            'version' => self::VERSION,
            'generated_at' => Carbon::now()->toIso8601String(),
            'migration_hash' => $migrationHash ?? $this->migrationHash(),
            'data' => $description->toPersistableArray(),
        ];

        $this->filesystem->put($this->pathFor($description->name), json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function flush(?string $table = null): void
    {
        if ($table) {
            $this->filesystem->delete($this->pathFor($table));

            return;
        }

        $this->filesystem->deleteDirectory($this->basePath());
    }

    public function needsRefresh(string $table): bool
    {
        $payload = $this->read($table);

        if (! $payload) {
            return true;
        }

        if (($payload['version'] ?? null) !== self::VERSION) {
            return true;
        }

        return ($payload['migration_hash'] ?? '') !== $this->migrationHash();
    }

    public function migrationHash(): string
    {
        if ($this->migrationHashCache !== null) {
            return $this->migrationHashCache;
        }

        $migrationPath = database_path('migrations');

        if (! $this->filesystem->exists($migrationPath)) {
            return $this->migrationHashCache = '';
        }

        $hashes = collect($this->filesystem->allFiles($migrationPath))
            ->map(fn (\SplFileInfo $file) => md5_file($file->getRealPath()))
            ->all();

        return $this->migrationHashCache = md5(implode('|', $hashes));
    }

    private function read(string $table): ?array
    {
        $path = $this->pathFor($table);

        if (! $this->filesystem->exists($path)) {
            return null;
        }

        $contents = $this->filesystem->get($path);
        $decoded = json_decode($contents, true);

        if (! is_array($decoded)) {
            return null;
        }

        return $decoded;
    }
}

