<?php

declare(strict_types=1);

namespace App\Services\Automation\Steps;

use App\Models\Automation;
use App\Models\AutomationStep;
use App\Models\Credential;
use App\Models\Server;
use App\Services\SshService;
use Illuminate\Validation\ValidationException;

class SshStepHandler
{
    public function __construct(
        protected SshService $sshService
    ) {}

    /**
     * @return array{output?:string,error?:string}
     */
    public function execute(Automation $automation, AutomationStep $step): array
    {
        $config = $step->config ?? [];
        $serverId = $config['server_id'] ?? null;
        $command = $config['command'] ?? null;
        $credentialId = $config['credential_id'] ?? null;

        if (! $serverId || ! $command) {
            return ['error' => 'SSH step missing server_id or command'];
        }

        $server = Server::query()->find($serverId);
        if (! $server) {
            return ['error' => 'Server not found'];
        }

        // Delegate to injected service to allow test mocking.
        $scriptModel = new \App\Models\Spork\Script([
            'name' => 'automation-step',
            'script' => $command,
        ]);
        $result = $this->sshService->run($scriptModel);
        $stdout = (string) ($result['stdout'] ?? '');
        $stderr = (string) ($result['stderr'] ?? '');

        if ($stderr !== '') {
            return ['error' => $stderr];
        }

        return ['output' => $stdout];
    }
}


