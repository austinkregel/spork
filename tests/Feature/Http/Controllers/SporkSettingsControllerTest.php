<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SporkSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_route_is_accessible()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/settings');

        $response->assertStatus(200);
    }

    public function test_settings_route_loads_expected_data()
    {
        $response = $this->actingAsUser()->get('http://spork.localhost/-/settings');

        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Index')
            ->has('config')
            ->has('settings')
        );
    }

    public function test_settings_can_be_updated()
    {
        $this->actingAsUser();

        $path = config_path('spork.php');
        $original = file_get_contents($path);

        try {
            $this->put('http://spork.localhost/-/settings', [
                'configs' => [
                    'spork' => [
                        'prefix' => 'testing',
                    ],
                ],
            ])->assertOk();

            exec('php -l ' . escapeshellarg($path), $out, $status);
            $this->assertSame(0, $status, implode("\n", $out));

            $updated = include $path;
            $this->assertSame('testing', $updated['prefix']);
        } finally {
            file_put_contents($path, $original);
        }
    }

    public function test_nested_arrays_are_rejected()
    {
        $this->actingAsUser();

        $path = config_path('spork.php');
        $original = file_get_contents($path);

        $this->put('http://spork.localhost/-/settings', [
            'configs' => [
                'spork' => [
                    'prefix' => ['not', 'scalar'],
                ],
            ],
        ])->assertStatus(422);

        $this->assertSame($original, file_get_contents($path));
    }

    public function test_unknown_config_file_is_ignored()
    {
        $this->actingAsUser();

        $file = config_path('does-not-exist.php');
        if (file_exists($file)) {
            unlink($file);
        }

        $this->put('http://spork.localhost/-/settings', [
            'configs' => [
                'does-not-exist' => [
                    'foo' => 'bar',
                ],
            ],
        ])->assertOk();

        $this->assertFileDoesNotExist($file);
    }

    public function test_malicious_string_does_not_break_config_file()
    {
        $this->actingAsUser();

        $path = config_path('spork.php');
        $original = file_get_contents($path);

        try {
            $payload = "bad';<?php echo 'evil';";

            $this->put('http://spork.localhost/-/settings', [
                'configs' => [
                    'spork' => [
                        'prefix' => $payload,
                    ],
                ],
            ])->assertOk();

            exec('php -l ' . escapeshellarg($path), $out, $status);
            $this->assertSame(0, $status, implode("\n", $out));

            $updated = include $path;
            $this->assertSame($payload, $updated['prefix']);
        } finally {
            file_put_contents($path, $original);
        }
    }

    public function test_path_traversal_attempt_is_prevented()
    {
        $this->actingAsUser();

        $file = config_path('malicious.php');
        if (file_exists($file)) {
            unlink($file);
        }

        $this->put('http://spork.localhost/-/settings', [
            'configs' => [
                '../malicious' => [
                    'foo' => 'bar',
                ],
            ],
        ])->assertOk();

        $this->assertFileDoesNotExist($file);
    }
}
