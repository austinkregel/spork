<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;
use App\Models\Credential;
use App\Services\Server\DigitalOceanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderOptionsController extends Controller
{
    public function __invoke(Request $request, Credential $credential): JsonResponse
    {
        abort_unless($credential->user_id === $request->user()->id, 403);
        abort_unless($credential->type === Credential::TYPE_SERVER, 422, 'Credential is not a compute provider.');

        if ($credential->service !== Credential::DIGITAL_OCEAN) {
            return response()->json([
                'regions' => [],
                'sizes' => [],
            ]);
        }

        $service = new DigitalOceanService($credential);

        $regions = collect($service->findAllRegions())
            ->map(fn ($region) => method_exists($region, 'toArray') ? $region->toArray() : (array) $region)
            ->map(fn ($region) => [
                'slug' => $region['slug'] ?? $region['name'] ?? null,
                'name' => $region['name'] ?? $region['slug'] ?? 'Region',
            ])
            ->values();

        $sizes = collect($service->findAllSizes())
            ->map(fn ($size) => is_array($size) ? $size : (array) $size)
            ->map(function ($size) {
                $slug = $size['slug'] ?? $size['id'] ?? null;
                $labelParts = array_filter([
                    $slug,
                    isset($size['vcpus']) ? sprintf('%s vCPU', $size['vcpus']) : null,
                    isset($size['memory']) ? sprintf('%s MB RAM', $size['memory']) : null,
                ]);

                return [
                    'slug' => $slug,
                    'description' => $size['description'] ?? $slug,
                    'memory' => $size['memory'] ?? null,
                    'vcpus' => $size['vcpus'] ?? null,
                    'disk' => $size['disk'] ?? null,
                    'label' => implode(' · ', $labelParts),
                ];
            })
            ->values();

        return response()->json([
            'regions' => $regions,
            'sizes' => $sizes,
        ]);
    }
}

