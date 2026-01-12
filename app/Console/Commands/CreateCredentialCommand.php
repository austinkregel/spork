<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Credential;
use App\Models\User;
use Illuminate\Console\Command;

class CreateCredentialCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:credential
        {--user-id= : User id that should own the credential}
        {--email= : User email that should own the credential}
        {--name= : Credential display name}
        {--type= : Credential type (e.g. finance)}
        {--service= : Credential service (e.g. privacy)}
        {--api-key= : api_key column value}
        {--secret-key= : secret_key column value}
        {--access-token= : access_token column value}
        {--refresh-token= : refresh_token column value}
        {--settings= : JSON string for settings}
        {--enabled-on= : enabled_on datetime (ISO8601 or Y-m-d H:i:s)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new credential';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Creating a new credential');

        $user = $this->resolveUser();
        if (! $user) {
            $this->error('Unable to resolve user. Provide --user-id= or --email=');

            return;
        }

        $type = (string) ($this->option('type') ?: $this->choice('What type of credential is this?', [
            Credential::TYPE_MATRIX,
            Credential::TYPE_DOMAIN,
            Credential::TYPE_EMAIL,
            Credential::TYPE_FINANCE,
            Credential::TYPE_CRM,
            Credential::TYPE_SSH,
            Credential::TYPE_SOURCE,
            Credential::TYPE_DEVELOPMENT,
            Credential::TYPE_REGISTRAR,
            Credential::TYPE_SERVER,
        ]));

        $service = (string) ($this->option('service') ?: $this->choice(
            sprintf('What service is this credential for? (type=%s)', $type),
            $this->servicesForType($type),
        ));

        $name = (string) ($this->option('name') ?: $this->ask('What is the credential display name?', $service));

        $settings = $this->parseSettings((string) ($this->option('settings') ?? ''));

        // Minimal, schema-accurate payload. Secrets are optional and depend on service.
        $data = array_filter([
            'user_id' => $user->id,
            'name' => $name,
            'type' => $type,
            'service' => $service,
            'api_key' => $this->option('api-key') ?? null,
            'secret_key' => $this->option('secret-key') ?? null,
            'access_token' => $this->option('access-token') ?? null,
            'refresh_token' => $this->option('refresh-token') ?? null,
            'settings' => $settings,
            'enabled_on' => $this->option('enabled-on') ?? null,
        ], fn ($value) => $value !== null);

        // Helpful interactive prompts for common cases when secrets weren’t provided.
        if (! $this->option('api-key') && $type === Credential::TYPE_FINANCE && $service === Credential::PRIVACY) {
            $data['api_key'] = $this->secret('Privacy API key (stored in api_key)');
        }

        Credential::query()->create($data);

        $this->info('Credential created successfully');
    }

    protected function resolveUser(): ?User
    {
        $userId = $this->option('user-id');
        $email = $this->option('email');

        if (is_string($userId) && $userId !== '') {
            return User::query()->find((int) $userId);
        }

        if (is_string($email) && $email !== '') {
            return User::query()->where('email', $email)->first();
        }

        // Fallback: pick the first user if any exist (common in dev).
        return User::query()->orderBy('id')->first();
    }

    /**
     * @return array<int, string>
     */
    protected function servicesForType(string $type): array
    {
        return match ($type) {
            Credential::TYPE_FINANCE => Credential::ALL_FINANCE_PROVIDERS,
            Credential::TYPE_DOMAIN => Credential::ALL_DOMAIN_PROVIDERS,
            Credential::TYPE_REGISTRAR => Credential::ALL_REGISTRAR_PROVIDERS,
            Credential::TYPE_SERVER => Credential::ALL_SERVER_PROVIDERS,
            Credential::TYPE_CRM => Credential::ALL_CRM_PROVIDERS,
            Credential::TYPE_SOURCE => Credential::ALL_SOURCE_PROVIDERS,
            default => array_values(array_unique(array_filter([
                Credential::PLAID,
                Credential::PRIVACY,
                Credential::CLOUDFLARE,
                Credential::NAMECHEAP,
                Credential::ENOM,
                Credential::DIGITAL_OCEAN,
                Credential::IMAP,
                Credential::MONICA,
            ]))),
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseSettings(string $json): array
    {
        $json = trim($json);
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }
}
