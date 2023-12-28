<?php

declare(strict_types=1);

namespace App\Operations;

use App\Models\Credential;
use App\Models\Server;
use App\Models\Spork\Script;
use App\Models\User;
use App\Services\SshService;
use App\Operations\Operation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerAction extends Operation
{
    protected $table = 'server_actions';

    public function run(): void
    {
        $service = new SshService(
            host: $this->server->internal_ip_address,
            username: $this->credential->settings['username'] ?? 'forge',
            publicKeyFile: $this->credential->getPublicKey(),
            privateKeyFile: $this->credential->getPrivateKey(),
            passKey: $this->credential->getPasskey()
        );  // replace with your server details

        [
            'stdout' => $out,
            'stderr' => $err,
        ] = $service->run($this->script->script);

        $this->error = $err;
        $this->output = $out;
        // modifying the Action will update the model by reference, and then when we execute, it should save.
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(Credential::class);
    }

    public function script(): BelongsTo
    {
        return $this->belongsTo(Script::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
