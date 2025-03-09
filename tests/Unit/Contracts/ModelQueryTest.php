<?php

declare(strict_types=1);

namespace Tests\Unit\Contracts;

use App\Contracts\ModelQuery;
use PHPUnit\Framework\TestCase;

class ModelQueryTest extends TestCase
{
    public function test_model_query_interface_exists(): void
    {
        $this->assertTrue(interface_exists(ModelQuery::class));
    }
}
