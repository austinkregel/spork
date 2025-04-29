<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkTagManagerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_manager_route_is_accessible()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/tag-manager');

        $response->assertStatus(200);
    }

    public function test_tag_manager_tag_route_is_accessible()
    {
        $tag = \App\Models\Tag::factory()->create([
            'name' => 'Test Tag',
        ]);
        $this->actingAsUser();

        $this->user->tags()->attach($tag);

        $response = $this->get("http://spork.localhost/-/tag-manager/{$tag->id}");

        $response->assertStatus(200);
    }

    public function test_tag_manager_route_loads_expected_data()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/tag-manager');

        $response->assertInertia(fn ($page) => $page
            ->component('Tags/Index')
            ->has('tags')
        );
    }

    public function test_tag_manager_tag_route_loads_expected_data()
    {
        $tag = \App\Models\Tag::factory()->create([
            'name' => 'Test Tag',
        ]);

        $response = $this->actingAsUser()->get("http://spork.localhost/-/tag-manager/{$tag->id}");

        $response->assertInertia(fn ($page) => $page
            ->component('Tags/Show')
            ->has('tag')
        );
    }
}
