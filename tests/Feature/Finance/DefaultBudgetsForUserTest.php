<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Models\Finance\Budget;
use App\Models\Person;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DefaultBudgetsForUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_ratio_based_budgets_from_person_estimated_income_and_attaches_tags(): void
    {
        $user = User::factory()->create();
        Person::factory()->create([
            'user_id' => $user->id,
            'estimated_income' => '120000',
        ]);

        $this->seedAutomaticTagsForUser($user, [
            'bills',
            'utilities',
            'transportation',
            'fast food/restaurants',
            'subscriptions',
            'personal/household',
        ]);

        event(new Registered($user));

        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'name' => 'Housing (Rent/Mortgage)',
            'frequency' => Budget::FREQUENCY_MONTHLY,
        ]);

        /** @var Budget $housing */
        $housing = Budget::query()
            ->where('user_id', $user->id)
            ->where('name', 'Housing (Rent/Mortgage)')
            ->firstOrFail();

        // 120,000 gross * 0.75 net / 12 = 7,500 net/month; 29.9% => 2,242.50
        $this->assertSame(2242.5, (float) $housing->amount);

        $bills = Tag::findFromString('bills', 'automatic');
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $bills->id,
            'taggable_type' => Budget::class,
            'taggable_id' => $housing->id,
        ]);
    }

    public function test_it_falls_back_to_default_income_when_person_income_missing(): void
    {
        $user = User::factory()->create();
        Person::factory()->create([
            'user_id' => $user->id,
            'estimated_income' => null,
        ]);

        $this->seedAutomaticTagsForUser($user, [
            'bills',
            'utilities',
            'transportation',
            'fast food/restaurants',
            'subscriptions',
            'personal/household',
        ]);

        event(new Registered($user));

        /** @var Budget $food */
        $food = Budget::query()
            ->where('user_id', $user->id)
            ->where('name', 'Food')
            ->firstOrFail();

        // default 75,000 gross * 0.75 / 12 = 4,687.50 net/month; 10.7% => 501.56
        $this->assertSame(501.56, (float) $food->amount);
    }

    public function test_it_does_not_create_default_budgets_if_user_already_has_a_budget(): void
    {
        $user = User::factory()->create();

        Budget::factory()->create([
            'user_id' => $user->id,
            'name' => 'Existing',
            'frequency' => Budget::FREQUENCY_MONTHLY,
            'interval' => 1,
            'count' => null,
            'started_at' => now('UTC')->startOfMonth(),
        ]);

        event(new Registered($user));

        $this->assertSame(1, Budget::query()->where('user_id', $user->id)->count());
    }

    /**
     * @param  array<int, string>  $names
     */
    protected function seedAutomaticTagsForUser(User $user, array $names): void
    {
        foreach ($names as $name) {
            /** @var Tag $tag */
            $tag = Tag::query()->create([
                'name' => ['en' => $name],
                'slug' => Str::slug($name),
                'type' => 'automatic',
                'must_all_conditions_pass' => false,
            ]);

            $user->tags()->syncWithoutDetaching([$tag->id]);
        }
    }
}
