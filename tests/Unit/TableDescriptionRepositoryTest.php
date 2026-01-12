<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Data\Crud\ActionMetadata;
use App\Data\Crud\FieldDefinition;
use App\Data\Crud\TableDescription;
use App\Repositories\TableDescriptionRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Tests\TestCase;

class TableDescriptionRepositoryTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->basePath = storage_path('framework/testing/crud-cache-'.Str::random(6));
        (new Filesystem)->deleteDirectory($this->basePath);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->basePath);
        parent::tearDown();
    }

    public function test_repository_persists_and_loads_descriptions(): void
    {
        $filesystem = new Filesystem;
        $repository = new TableDescriptionRepository($filesystem, $this->basePath);
        $description = new TableDescription(
            name: 'example_models',
            modelClass: null,
            prettyName: 'Example',
            fieldDefinitions: [
                new FieldDefinition('id', 'bigint', 'number'),
            ],
            actions: [
                ActionMetadata::fromArray(['class' => 'ExampleAction']),
            ]
        );

        $repository->put($description, 'known-hash');
        $this->assertFileExists($repository->pathFor('example_models'));

        $loaded = $repository->get('example_models');
        $this->assertSame('example_models', $loaded->name);
        $this->assertTrue($repository->needsRefresh('example_models'));
    }
}
