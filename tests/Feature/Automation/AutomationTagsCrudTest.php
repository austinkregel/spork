<?php

declare(strict_types=1);

namespace Tests\Feature\Automation;

use App\Models\Condition;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomationTagsCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_an_automation_tag_and_is_redirected_to_show(): void
    {
        $this->actingAsUser();

        $response = $this->post('http://spork.localhost/-/automations/tags', [
            'name' => 'My Custom Tag',
            'type' => 'automatic',
            'must_all_conditions_pass' => false,
        ]);

        $response->assertStatus(302);
        $location = (string) $response->headers->get('Location');
        $this->assertStringContainsString('http://spork.localhost/-/automations/tags/', $location);

        $tagId = (int) basename($location);
        $tag = Tag::query()->find($tagId);

        $this->assertNotNull($tag);
        $this->assertTrue($this->user->tags()->whereKey($tag->getKey())->exists());
        $name = is_array($tag->name) ? ($tag->name['en'] ?? null) : $tag->name;
        $this->assertSame('My Custom Tag', $name);

        $response->assertRedirect("http://spork.localhost/-/automations/tags/{$tag->id}");
    }

    public function test_creating_a_duplicate_user_tag_redirects_instead_of_erroring(): void
    {
        $this->actingAsUser();

        $response = $this->post('http://spork.localhost/-/automations/tags', [
            'name' => 'subscriptions',
            'type' => 'automatic',
            'must_all_conditions_pass' => false,
        ]);

        $response->assertStatus(302);
        $this->assertStringContainsString('http://spork.localhost/-/automations/tags/', (string) $response->headers->get('Location'));
    }

    public function test_user_can_update_their_tag_name_type_and_match_mode(): void
    {
        $this->actingAsUser();

        $tag = Tag::factory()->create([
            'name' => ['en' => 'Old Name'],
            'slug' => 'old-name',
            'type' => 'automatic',
            'must_all_conditions_pass' => false,
        ]);

        $this->user->tags()->attach($tag);

        $response = $this->patch("http://spork.localhost/-/automations/tags/{$tag->id}", [
            'name' => 'New Name',
            'type' => '',
            'must_all_conditions_pass' => true,
        ]);

        $response->assertStatus(302);

        $tag->refresh();

        $name = is_array($tag->name) ? ($tag->name['en'] ?? null) : $tag->name;
        $this->assertSame('New Name', $name);
        $this->assertSame('new-name', $tag->slug);
        $this->assertNull($tag->type);
        $this->assertFalse((bool) $tag->must_all_conditions_pass, 'Non-automatic tags should not require match mode');
    }

    public function test_user_can_manage_conditions_on_their_tag(): void
    {
        $this->actingAsUser();

        $tag = Tag::factory()->create([
            'name' => ['en' => 'Utilities'],
            'slug' => 'utilities',
            'type' => 'automatic',
        ]);
        $this->user->tags()->attach($tag);

        $create = $this->postJson("http://spork.localhost/-/automations/tags/{$tag->id}/conditions", [
            'parameter' => 'transaction.name',
            'comparator' => 'LIKE',
            'value' => 'power',
        ]);

        $create->assertOk()->assertJsonStructure(['id', 'parameter', 'comparator', 'value']);

        $conditionId = (int) $create->json('id');
        $condition = Condition::query()->find($conditionId);
        $this->assertNotNull($condition);
        $this->assertSame(Tag::class, $condition->conditionable_type);
        $this->assertSame($tag->id, (int) $condition->conditionable_id);

        $update = $this->putJson("http://spork.localhost/-/automations/tags/{$tag->id}/conditions/{$conditionId}", [
            'value' => 'electric',
        ]);

        $update->assertOk()->assertJsonFragment(['value' => 'electric']);

        $delete = $this->delete("http://spork.localhost/-/automations/tags/{$tag->id}/conditions/{$conditionId}");
        $delete->assertStatus(204);

        $this->assertNull(Condition::query()->find($conditionId));
    }

    public function test_user_cannot_update_or_add_conditions_to_someone_elses_tag(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()->subHour()]);
        $attacker = User::factory()->create(['email_verified_at' => now()->subHour()]);

        $tag = Tag::factory()->create([
            'name' => ['en' => 'Private'],
            'slug' => 'private',
            'type' => 'automatic',
        ]);
        $owner->tags()->attach($tag);

        $this->actingAs($attacker);

        $this->patch("http://spork.localhost/-/automations/tags/{$tag->id}", [
            'name' => 'Hacked',
        ])->assertStatus(404);

        $this->postJson("http://spork.localhost/-/automations/tags/{$tag->id}/conditions", [
            'parameter' => 'transaction.name',
            'comparator' => 'LIKE',
            'value' => 'x',
        ])->assertStatus(404);
    }
}
