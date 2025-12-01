<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Development\DescribeTable\RelationInspector;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Tests\TestCase;
use Tests\Fixtures\Models\ExampleModel;

class RelationInspectorTest extends TestCase
{
    public function testInspectReturnsRelationMethods(): void
    {
        $model = new class extends ExampleModel
        {
            public function related(): HasOne
            {
                return $this->hasOne(ExampleModel::class, 'parent_id');
            }
        };

        $inspector = new RelationInspector();
        $relations = $inspector->inspect($model);

        $this->assertSame(['related'], $relations);
    }
}

