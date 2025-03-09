<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Spork;

use App\Actions\Spork\CustomAction;
use PHPUnit\Framework\TestCase;

class CustomActionTest extends TestCase
{
    public function test_execute_method(): void
    {
        $customAction = $this->getMockForAbstractClass(CustomAction::class);

        $this->assertEquals('Set Namecheap DNS', $customAction->name);
        $this->assertEquals('custom-action', $customAction->slug);
        $this->assertEquals([], $customAction->models);
    }
}
