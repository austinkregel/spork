<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\ActionInterface;
use App\Data\Crud\ActionMetadata;
use App\Services\Development\DescribeTable\ActionMetadataCollector;
use Tests\Fixtures\Models\ExampleModel;
use Tests\TestCase;

class ActionMetadataCollectorTest extends TestCase
{
    public function testCollectReturnsActionsForModel(): void
    {
        $collector = new ActionMetadataCollector([ExampleAction::class]);
        $actions = $collector->collect(new ExampleModel());

        $this->assertCount(1, $actions);
        $this->assertInstanceOf(ActionMetadata::class, $actions[0]);
        $this->assertSame(ExampleAction::class, $actions[0]->className);
    }
}

class ExampleAction implements ActionInterface
{
    public array $models = [
        ExampleModel::class,
    ];

    public function fields(): array
    {
        return ['name' => 'text'];
    }
}

