<?php

declare(strict_types=1);

namespace App\Services\Automation;

use App\Models\Automation;
use App\Models\Credential;
use App\Models\Server;
use App\Models\Spork\Script;
use App\Models\Tag;
use App\Models\User;
use App\Operations\ServerAction;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AutomationDemoSeeder
{
    /**
     * Seed a deterministic set of demo automations for the provided user.
     *
     * @return array<int, array{slug:string,name:string,steps:int,created:bool}>
     */
    public function seed(User $user): array
    {
        return DB::transaction(function () use ($user) {
            $resources = $this->ensureResources($user);
            $definitions = $this->definitions($user, $resources);

            $summaries = [];

            foreach ($definitions as $definition) {
                $automation = Automation::query()->updateOrCreate(
                    [
                        'slug' => $definition['slug'],
                        'user_id' => $user->id,
                    ],
                    Arr::except($definition, ['steps'])
                );

                $automation->steps()->delete();

                foreach ($definition['steps'] as $order => $step) {
                    $automation->steps()->create([
                        'order' => $order + 1,
                        'type' => $step['type'],
                        'config' => $step['config'],
                    ]);
                }

                $summaries[] = [
                    'slug' => $automation->slug,
                    'name' => $automation->name,
                    'steps' => count($definition['steps']),
                    'created' => $automation->wasRecentlyCreated,
                ];
            }

            return $summaries;
        });
    }

    protected function ensureResources(User $user): array
    {
        $priorityTag = Tag::findOrCreate('Automation Demo - Priority', 'automations');
        $watchTag = Tag::findOrCreate('Automation Demo - Watchlist', 'automations');
        $user->attachTag($priorityTag);
        $user->attachTag($watchTag);

        $credential = $user->credentials()->firstOrCreate(
            [
                'name' => 'Demo SSH Credential',
                'type' => Credential::TYPE_SSH,
                'service' => Credential::TYPE_SSH,
            ],
            [
                'settings' => [
                    'pub_key_file' => base_path('tests/data/test_key.pub'),
                    'private_key_file' => base_path('tests/data/test_key'),
                    'pass_key' => null,
                ],
            ]
        );

        $server = Server::query()->firstOrCreate(
            [
                'server_id' => 'demo-automation-server',
            ],
            [
                'name' => 'Demo Automation Server',
                'vcpu' => 2,
                'memory' => 2048,
                'disk' => 40,
                'status' => 'active',
                'ip_address' => '127.0.0.1',
                'internal_ip_address' => '127.0.0.1',
                'os' => 'Ubuntu 22.04 LTS',
            ]
        );

        if (($server->credential_id ?? null) !== $credential->id) {
            $server->credential()->associate($credential);
            $server->save();
        }

        $script = Script::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'Demo Maintenance Script',
            ],
            [
                'language' => 'bash',
                'script' => <<<'BASH'
#!/usr/bin/env bash
echo "Demo maintenance script executed at $(date -u)"
BASH,
            ]
        );

        return [
            'tags' => [
                'priority' => $priorityTag,
                'watchlist' => $watchTag,
            ],
            'credential' => $credential,
            'server' => $server,
            'script' => $script,
        ];
    }

    protected function definitions(User $user, array $resources): array
    {
        $server = $resources['server'];
        $credential = $resources['credential'];
        $script = $resources['script'];
        $priorityTag = $resources['tags']['priority'];
        $watchTag = $resources['tags']['watchlist'];

        return [
            [
                'slug' => 'demo-site-watchdog',
                'name' => 'Demo Site Watchdog',
                'enabled' => false,
                'cron_expression' => '*/30 * * * *',
                'timezone' => 'UTC',
                'pacing_per_host_ms' => 0,
                'max_concurrency' => 1,
                'steps' => [
                    [
                        'type' => 'http',
                        'config' => [
                            'method' => 'GET',
                            'url' => 'https://jsonplaceholder.typicode.com/todos/1',
                            'timeout_ms' => 8000,
                            'headers' => [
                                ['key' => 'Accept', 'value' => 'application/json'],
                                ['key' => 'User-Agent', 'value' => 'SporkAutomationDemo/1.0'],
                            ],
                        ],
                    ],
                    [
                        'type' => 'condition',
                        'config' => [
                            'source' => 'previous',
                            'on_false' => 'skip',
                            'conditions' => [
                                [
                                    'parameter' => 'completed',
                                    'comparator' => 'EQUALS',
                                    'value' => false,
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'notify',
                        'config' => [
                            'title' => 'Demo Watchdog Result',
                            'message' => 'JSONPlaceholder responded & the todo is still incomplete.',
                            'level' => 'info',
                            'user_ids' => [$user->id],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'demo-customer-onboarding',
                'name' => 'Demo Customer Onboarding',
                'enabled' => false,
                'cron_expression' => '0 9 * * 1-5',
                'timezone' => 'UTC',
                'pacing_per_host_ms' => 500,
                'max_concurrency' => 2,
                'steps' => [
                    [
                        'type' => 'wait',
                        'config' => [
                            'ms' => 1500,
                        ],
                    ],
                    [
                        'type' => 'tagging',
                        'config' => [
                            'target' => [
                                'type' => User::class,
                                'id' => $user->id,
                            ],
                            'action' => 'attach',
                            'tag_ids' => [$priorityTag->id, $watchTag->id],
                        ],
                    ],
                    [
                        'type' => 'notify',
                        'config' => [
                            'title' => 'Demo Onboarding Reminder',
                            'message' => 'Tags applied to the demo user to mimic onboarding.',
                            'level' => 'success',
                            'user_ids' => [$user->id],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'demo-infrastructure-maintenance',
                'name' => 'Demo Infrastructure Maintenance',
                'enabled' => false,
                'cron_expression' => '0 2 * * 0',
                'timezone' => 'UTC',
                'pacing_per_host_ms' => 0,
                'max_concurrency' => 1,
                'steps' => [
                    [
                        'type' => 'dusk',
                        'config' => [
                            'action' => 'Visit status page and capture banner',
                            'target' => 'https://status.example.com',
                        ],
                    ],
                    [
                        'type' => 'ssh',
                        'config' => [
                            'server_id' => $server->id,
                            'command' => 'echo "Demo maintenance check" && uptime',
                            'credential_id' => $credential->id,
                        ],
                    ],
                    [
                        'type' => 'operation',
                        'config' => [
                            'operation' => ServerAction::class,
                            'queue' => 'default',
                            'attributes' => [
                                'user_id' => $user->id,
                                'server_id' => $server->id,
                                'credential_id' => $credential->id,
                                'script_id' => $script->id,
                            ],
                        ],
                    ],
                    [
                        'type' => 'notify',
                        'config' => [
                            'title' => 'Demo Infra Run Created',
                            'message' => 'A server action operation was scheduled by the demo automation.',
                            'level' => 'warning',
                            'user_ids' => [$user->id],
                        ],
                    ],
                ],
            ],
        ];
    }
}


