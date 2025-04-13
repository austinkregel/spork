<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkFileManagerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_file_manager_route_is_accessible()
    {
        $response = $this->get('/-/file-manager');

        $response->assertStatus(200);
    }

    public function test_file_manager_route_loads_expected_data()
    {
        $response = $this->get('/-/file-manager');

        $response->assertInertia(fn ($page) => $page
            ->component('FileManager')
            ->has('files')
        );
    }
}
