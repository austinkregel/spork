<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Http\Controllers\Controller;
use App\Models\Credential;
use App\Models\Deployment;
use App\Models\Domain;
use App\Models\Server;

class DeploymentController extends Controller
{
    public function deploy(Deployment $deployment)
    {
        $deployment->load([
            'servers.tags', 'domains',
            'credentials',
        ]);

        $cloudflareCredential = $deployment->credentials()->where('service', Credential::CLOUDFLARE)->first();
        $namecheapCredential = $deployment->credentials()->where('service', Credential::NAMECHEAP)->first();

        if (in_array(null, [$cloudflareCredential, $namecheapCredential])) {
            return response()->json([
                'message' => 'Missing required credentials for deployment.',
            ], 422);
        }

        // The actual deployment implementation has been removed now that Forge is no longer used.
        return response([], 200);
    }

    public function attach(Deployment $deployment)
    {
        request()->validate([
            'resource_type' => \Illuminate\Validation\Rule::in([
                \App\Models\Credential::class,
                Domain::class,
                Server::class,
            ]),
        ]);

        if (\DB::table('deployment_resources')->where([
            'resource_type' => request()->get('resource_type'),
            'resource_id' => request()->get('resource_id'),
            'deployment_id' => $deployment->id,
        ])->exists()) {
            return response([
                'message' => 'Already exists',
            ], 422);
        }

        \DB::table('deployment_resources')->insert([
            'resource_type' => request()->get('resource_type'),
            'resource_id' => request()->get('resource_id'),
            'deployment_id' => $deployment->id,
            'settings' => json_encode($this->filterSettingsForResource(request()->get('resource_type'), request()->get('resource_id'))),
        ]);
    }

    public function detach(Deployment $deployment)
    {
        request()->validate([
            'resource_type' => \Illuminate\Validation\Rule::in([
                \App\Models\Credential::class,
                Domain::class,
                Server::class,
            ]),
        ]);

        \DB::table('deployment_resources')->where([
            'resource_type' => request()->get('resource_type'),
            'resource_id' => request()->get('resource_id'),
            'deployment_id' => $deployment->id,
        ])->delete();
    }

    protected function filterSettingsForResource(string $classType, $id)
    {
        $settings = [];
        $implementedInterfaces = class_implements($classType);
        $relations = [];

        if (in_array(\App\Models\Taggable::class, $implementedInterfaces)) {
            $relations[] = 'tags';
        }

        /** @var Server $instance */
        $instance = $classType::query()
            ->with($relations)
            ->find($id);

        $tags = $instance->tags;

        if ($tags?->map?->name?->map?->en?->contains('loadbalancer')) {
            $settings['loadbalancer'] = true;
        }

        return $settings;
    }
}
