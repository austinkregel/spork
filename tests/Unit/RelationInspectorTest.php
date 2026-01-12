<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Development\DescribeTable\RelationInspector;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Tests\Fixtures\Models\ExampleModel;
use Tests\TestCase;

class RelationInspectorTest extends TestCase
{
    public function test_inspect_returns_relation_methods(): void
    {
        $model = new class extends ExampleModel
        {
            public function related(): HasOne
            {
                return $this->hasOne(ExampleModel::class, 'parent_id');
            }
        };

        $inspector = new RelationInspector;
        $relations = $inspector->inspect($model);

        $this->assertSame(['related'], $relations);
    }
}
