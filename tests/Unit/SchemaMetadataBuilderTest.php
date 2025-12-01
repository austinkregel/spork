<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Data\Crud\FieldDefinition;
use App\Services\Development\DescribeTable\SchemaMetadataBuilder;
use Illuminate\Database\ConnectionInterface;
use Mockery;
use Tests\TestCase;

class SchemaMetadataBuilderTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testBuildReturnsFieldDefinitionsAndFilters(): void
    {
        $connection = Mockery::mock(ConnectionInterface::class);
        $descriptionRows = [
            (object) ['Field' => 'id', 'Type' => 'bigint(20)', 'Null' => 'NO', 'Extra' => 'auto_increment', 'Default' => null],
            (object) ['Field' => 'name', 'Type' => 'varchar(255)', 'Null' => 'NO', 'Extra' => '', 'Default' => null],
        ];
        $indexes = [
            (object) ['Column_name' => 'name'],
            (object) ['Column_name' => 'name'],
        ];

        $connection->shouldReceive('select')->once()->with('describe example_models')->andReturn($descriptionRows);
        $connection->shouldReceive('select')->once()->with('show indexes from example_models')->andReturn($indexes);

        $builder = new SchemaMetadataBuilder($connection);
        $result = $builder->build('example_models');

        $this->assertCount(2, $result['field_definitions']);
        $this->assertInstanceOf(FieldDefinition::class, $result['field_definitions'][0]);
        $this->assertSame(['name'], $result['filters']);
    }
}

