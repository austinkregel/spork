<?php

declare(strict_types=1);

namespace Tests\Feature\Banking;

use App\Models\Credential;
use App\Models\Finance\Budget;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_budget_with_tags(): void
    {
        $user = User::factory()->create();
        Credential::factory()->create(['user_id' => $user->id]);

        /** @var Tag $tag */
        $tag = Tag::factory()->create(['type' => 'automatic']);
        $user->attachTag($tag);

        $this->actingAs($user)
            ->post('http://spork.localhost/-/banking/budgets', [
                'name' => 'Groceries',
                'amount' => 500,
                'frequency' => 'MONTHLY',
                'interval' => 1,
                'started_at' => now('UTC')->startOfMonth()->toDateString(),
                'tag_ids' => [$tag->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'name' => 'Groceries',
            'frequency' => 'MONTHLY',
        ]);

        /** @var Budget $budget */
        $budget = Budget::query()->where('user_id', $user->id)->where('name', 'Groceries')->firstOrFail();

        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag->id,
            'taggable_type' => Budget::class,
            'taggable_id' => $budget->id,
        ]);
    }

    public function test_user_can_update_budget_and_sync_tags(): void
    {
        $user = User::factory()->create();
        Credential::factory()->create(['user_id' => $user->id]);

        /** @var Tag $tagA */
        $tagA = Tag::factory()->create(['type' => 'automatic']);
        /** @var Tag $tagB */
        $tagB = Tag::factory()->create(['type' => 'automatic']);
        $user->attachTags([$tagA, $tagB]);

        /** @var Budget $budget */
        $budget = Budget::factory()->create([
            'user_id' => $user->id,
            'name' => 'Bills',
            'amount' => 100,
            'frequency' => 'MONTHLY',
            'interval' => 1,
            'count' => null,
            'started_at' => now('UTC')->startOfMonth(),
        ]);

        $budget->tags()->sync([$tagA->id]);

        $this->actingAs($user)
            ->put('http://spork.localhost/-/banking/budgets/'.$budget->id, [
                'name' => 'Bills Updated',
                'amount' => 250,
                'frequency' => 'MONTHLY',
                'interval' => 1,
                'started_at' => now('UTC')->startOfMonth()->toDateString(),
                'tag_ids' => [$tagB->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'user_id' => $user->id,
            'name' => 'Bills Updated',
            'amount' => 250,
        ]);

        $this->assertDatabaseMissing('taggables', [
            'tag_id' => $tagA->id,
            'taggable_type' => Budget::class,
            'taggable_id' => $budget->id,
        ]);

        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tagB->id,
            'taggable_type' => Budget::class,
            'taggable_id' => $budget->id,
        ]);
    }

    public function test_user_cannot_attach_unowned_tag_to_budget(): void
    {
        $user = User::factory()->create();
        Credential::factory()->create(['user_id' => $user->id]);

        /** @var Tag $unowned */
        $unowned = Tag::factory()->create(['type' => 'automatic']);

        $this->actingAs($user)
            ->post('http://spork.localhost/-/banking/budgets', [
                'name' => 'Bad Budget',
                'amount' => 10,
                'frequency' => 'MONTHLY',
                'interval' => 1,
                'started_at' => now('UTC')->startOfMonth()->toDateString(),
                'tag_ids' => [$unowned->id],
            ])
            ->assertSessionHasErrors(['tag_ids.0']);
    }
}
