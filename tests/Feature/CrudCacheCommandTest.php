<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Data\Crud\ActionMetadata;
use App\Data\Crud\FieldDefinition;
use App\Data\Crud\TableDescription;
use App\Repositories\TableDescriptionRepository;
use App\Services\Development\DescribeTable\TableDescriptionFactory;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Mockery;
use Tests\Fixtures\Models\ExampleModel;
use Tests\TestCase;

class CrudCacheCommandTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->basePath = storage_path('framework/testing/crud-cache-command');
        (new Filesystem())->deleteDirectory($this->basePath);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        (new Filesystem())->deleteDirectory($this->basePath);
        $this->app->forgetInstance(TableDescriptionFactory::class);
        $this->app->forgetInstance(TableDescriptionRepository::class);
        parent::tearDown();
    }

    public function testCommandCachesTableMetadata(): void
    {
        $repository = new TableDescriptionRepository(new Filesystem(), $this->basePath);
        $this->app->instance(TableDescriptionRepository::class, $repository);

        $description = new TableDescription(
            name: 'example_models',
            modelClass: ExampleModel::class,
            prettyName: 'ExampleModel',
            fieldDefinitions: [
                new FieldDefinition('id', 'bigint', 'number', sortable: true),
            ],
            actions: [
                ActionMetadata::fromArray(['class' => 'ExampleAction']),
            ],
        );

        $factory = Mockery::mock(TableDescriptionFactory::class);
        $factory->shouldReceive('forModel')
            ->once()
            ->with(Mockery::type(ExampleModel::class), false)
            ->andReturn($description);

        $this->app->instance(TableDescriptionFactory::class, $factory);

        $this->artisan('crud:cache', ['--model' => ExampleModel::class, '--force' => true])
            ->assertExitCode(Command::SUCCESS);

        $this->assertFileExists($repository->pathFor('example_models'));
    }
}

