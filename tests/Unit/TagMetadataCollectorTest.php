<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Development\DescribeTable\TagMetadataCollector;
use Illuminate\Database\Eloquent\Builder;
use Mockery;
use Tests\Fixtures\Models\ExampleModel;
use Tests\Fixtures\Models\ExampleTaggableModel;
use Tests\TestCase;

class TagMetadataCollectorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCollectReturnsEmptyCollectionForNonTaggableModels(): void
    {
        $collector = new TagMetadataCollector();
        $result = $collector->collect(new ExampleModel());

        $this->assertTrue($result->isEmpty());
    }

    public function testCollectReturnsTagsForTaggableModels(): void
    {
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('whereNull')->with('type')->andReturnSelf();
        $query->shouldReceive('orWhere')->with('type', 'example_model')->andReturnSelf();
        $query->shouldReceive('get')->andReturn(collect([['name' => 'alpha']]));

        $collector = new TagMetadataCollector(fn () => $query);
        $result = $collector->collect(new ExampleTaggableModel());

        $this->assertCount(1, $result);
        $this->assertSame('alpha', $result[0]['name']);
    }
}

