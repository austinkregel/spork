<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Spork;

use App\Actions\Spork\ApplyTagToModelAction;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplyTagToModelActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_tag_to_model(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();

        $action = new ApplyTagToModelAction();
        $action->apply($user, $tag);

        $this->assertTrue($user->tags->contains($tag));
    }
}
