<?php

declare(strict_types=1);

namespace Tests\Unit\Automation;

use App\Models\Automation;
use App\Models\AutomationStep;
use App\Models\Tag;
use App\Models\User;
use App\Services\Automation\Steps\TaggingStepHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaggingStepHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_attach_tags_to_target()
    {
        $this->actingAsUser();
        $automation = Automation::create([
            'user_id' => $this->user->id,
            'name' => 'Tagging A',
            'enabled' => true,
        ]);

        $tag = Tag::factory()->create();
        $this->user->attachTag($tag);

        $step = new AutomationStep([
            'type' => 'tagging',
            'config' => [
                'target' => ['type' => User::class, 'id' => $this->user->id],
                'action' => 'attach',
                'tag_ids' => [$tag->id],
            ],
        ]);

        $handler = new TaggingStepHandler();
        $res = $handler->execute($automation, $step);
        $this->assertArrayHasKey('output', $res);
    }
}


