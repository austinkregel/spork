<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkAutomationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_automation_route_is_accessible()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/automations/workspace');

        $response->assertStatus(200);
    }

    public function test_automation_tags_route_is_accessible()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/automations/tags');

        $response->assertStatus(200);
    }

    public function test_automation_tag_route_is_accessible()
    {
        $tag = Tag::factory()->create();
        $this->actingAsUser();

        $this->user->tags()->attach($tag);

        $response = $this->get("http://spork.localhost/-/automations/tags/{$tag->id}");

        $response->assertStatus(200);
    }

    public function test_automation_route_loads_expected_data()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/automations/workspace');

        $response->assertInertia(fn ($page) => $page
            ->component('Automation/Index')
            ->has('blueprints')
        );
    }

    public function test_automation_tag_route_loads_expected_data()
    {
        $tag = Tag::factory()->create();

        $this->actingAsUser();
        $this->user->tags()->attach($tag);

        $response = $this->get("http://spork.localhost/-/automations/tags/{$tag->id}");

        $response->assertInertia(fn ($page) => $page
            ->component('Automation/TagShow')
            ->has('tag')
        );
    }
}
