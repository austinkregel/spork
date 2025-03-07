<?php

declare(strict_types=1);

namespace Tests\Integration\ConditionalLogic;

use App\Models\Tag;
use App\Services\ConditionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConditionalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_if_no_conditions_will_pass(): void
    {
        $service = new ConditionService(
            $logger = \Mockery::mock(\Psr\Log\LoggerInterface::class)
        );

        $tag = Tag::factory()->create([
            'name' => [
                'en' => 'test',
            ],
        ]);

        $this->assertTrue($service->process($tag, ['name' => 'test']));
    }

    public function test_will_be_falsy_with_false_condition(): void
    {
        $service = new ConditionService(
            $logger = \Mockery::mock(\Psr\Log\LoggerInterface::class)
        );

        $logger->shouldReceive('info')
            ->once()
            ->with('Condition: transaction.name EQUALS test', [
                'passes_condition' => false,
                'value' => null,
            ]);

        $tag = Tag::factory()->create([
            'name' => [
                'en' => 'test',
            ],
        ]);

        $tag->conditions()->create([
            'parameter' => 'transaction.name',
            'comparator' => 'EQUALS',
            'value' => 'test',
        ]);

        $this->assertFalse($service->process($tag, ['name' => 'test']));
    }

    public function test_will_be_falsy_with_false_condition_but_when_data_is_set_we_log_value_correctly(): void
    {
        $service = new ConditionService(
            $logger = \Mockery::mock(\Psr\Log\LoggerInterface::class)
        );

        $logger->shouldReceive('info')
            ->once()
            ->with('Condition: transaction.name EQUALS test', [
                'passes_condition' => false,
                'value' => 'Netflix',
            ]);

        $tag = Tag::factory()->create([
            'name' => [
                'en' => 'test',
            ],
        ]);

        $tag->conditions()->create([
            'parameter' => 'transaction.name',
            'comparator' => 'EQUALS',
            'value' => 'test',
        ]);

        $this->assertFalse($service->process($tag, [
            'transaction' => [
                'name' => 'Netflix',
            ],
        ]));
    }
}
