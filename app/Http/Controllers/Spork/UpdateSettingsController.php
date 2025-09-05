<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use Illuminate\Http\Request;
use Winter\LaravelConfigWriter\ArrayFile;

class UpdateSettingsController
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'configs' => ['required', 'array'],
            'configs.*' => ['array'],
            'configs.*.*' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if (is_array($value) || is_object($value)) {
                        $fail('The '.$attribute.' must be a scalar value.');
                    }
                },
            ],
        ]);

        foreach ($validated['configs'] as $file => $values) {
            $file = basename($file);
            $path = config_path($file . '.php');

            if (! file_exists($path)) {
                continue;
            }

            $original = file_get_contents($path);

            $config = ArrayFile::open($path);

            foreach ($values as $key => $value) {
                $config->set($key, $value);
            }

            $config->write();

            try {
                token_get_all(file_get_contents($path));
            } catch (\ParseError $e) {
                file_put_contents($path, $original);

                throw new \RuntimeException(
                    sprintf(
                        'Invalid PHP written to %s: %s',
                        $file,
                        $e->getMessage()
                    ),
                    previous: $e
                );
            }
        }

        return response()->json(['message' => 'Settings updated']);
    }
}

