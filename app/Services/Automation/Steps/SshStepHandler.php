<?php

declare(strict_types=1);

namespace App\Services\Automation\Steps;

use App\Models\Automation;
use App\Models\AutomationStep;
use App\Models\Credential;
use App\Models\Server;
use App\Models\Spork\Script;
use App\Services\SshService;
use Illuminate\Validation\ValidationException;

class SshStepHandler
{
    public function __construct(
        protected ?SshService $sshService = null,
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
        $sshService = $this->resolveService($server, $credentialId);
        if (is_array($sshService) && isset($sshService['error'])) {
            return $sshService;
        }

        $scriptModel = new Script([
            'name' => 'automation-step',
            'script' => $command,
        ]);
        $result = $sshService->run($scriptModel);
        $stdout = (string) ($result['stdout'] ?? '');
        $stderr = (string) ($result['stderr'] ?? '');

        if ($stderr !== '') {
            return ['error' => $stderr];
        }

        return ['output' => $stdout];
    }

    /**
     * @return array{error:string}|SshService
     */
    protected function resolveService(Server $server, ?int $credentialId): array|SshService
    {
        try {
            if ($this->sshService) {
                return $this->sshService;
            }

            if ($credentialId) {
                $credential = Credential::query()->find($credentialId);
                if (! $credential) {
                    return ['error' => 'Credential not found'];
                }

                return new SshService(
                    host: $server->internal_ip_address ?? $server->ip_address ?? '127.0.0.1',
                    username: $credential->settings['username'] ?? 'root',
                    publicKeyFile: $credential->settings['pub_key_file'] ?? '',
                    privateKeyFile: $credential->settings['private_key_file'] ?? '',
                    port: $credential->settings['port'] ?? 22,
                    passKey: $credential->settings['pass_key'] ?? null
                );
            }

            return SshService::fromServer($server);
        } catch (\Throwable $exception) {
            return ['error' => 'Unable to initialize SSH connection: '.$exception->getMessage()];
        }
    }
}


