<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkTagManagerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_manager_route_is_accessible()
    {
        $response = $this->get('/-/tag-manager');

        $response->assertStatus(200);
    }

    public function test_tag_manager_tag_route_is_accessible()
    {
        $tag = \App\Models\Tag::factory()->create();

        $response = $this->get("/-/tag-manager/{$tag->id}");

        $response->assertStatus(200);
    }

    public function test_tag_manager_route_loads_expected_data()
    {
        $response = $this->get('/-/tag-manager');

        $response->assertInertia(fn ($page) => $page
            ->component('TagManager')
            ->has('tags')
        );
    }

    public function test_tag_manager_tag_route_loads_expected_data()
    {
        $tag = \App\Models\Tag::factory()->create();

        $response = $this->get("/-/tag-manager/{$tag->id}");

        $response->assertInertia(fn ($page) => $page
            ->component('TagManager/Show')
            ->has('tag')
        );
    }
}
