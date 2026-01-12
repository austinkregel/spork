<?php

declare(strict_types=1);

namespace App\Console\Commands\Infrastructure;

use App\Models\Credential;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateMonitorBridgeCredentialCommand extends Command
{
    protected $signature = 'infrastructure:monitor-bridge-credential
        {--user-id= : User id that should own the credential}
        {--email= : User email that should own the credential}
        {--name=Monitor Bridge : Credential display name}
        {--force : Create even if an existing monitor-bridge credential already exists for this user}';

    protected $description = 'Create a monitor bridge credential and print the token once (for SPORK_INGEST_TOKEN).';

    public function handle(): int
    {
        $user = $this->resolveUser();

        if (! $user) {
            $this->error('Unable to resolve user. Provide --user-id= or --email=');

            return self::FAILURE;
        }

        $existing = Credential::query()
            ->where('user_id', $user->id)
            ->where('type', Credential::TYPE_DEVELOPMENT)
            ->where('service', 'monitor-bridge')
            ->first();

        if ($existing && ! $this->option('force')) {
            $this->error('A monitor-bridge credential already exists for this user. Use --force to create another.');

            return self::FAILURE;
        }

        $apiKey = Str::random(64);

        $credential = Credential::query()->create([
            'user_id' => $user->id,
            'type' => Credential::TYPE_DEVELOPMENT,
            'service' => 'monitor-bridge',
            'name' => (string) $this->option('name'),
            'api_key' => $apiKey,
            'settings' => [
                'kind' => 'monitor-bridge',
            ],
        ]);

        $this->newLine();
        $this->info('Monitor bridge credential created.');
        $this->line(sprintf('User: %s (%s)', $user->email, $user->id));
        $this->line(sprintf('Credential id: %s', $credential->id));
        $this->newLine();

        $this->warn('Token (print once):');
        $this->line($apiKey);
        $this->newLine();

        $this->line('Suggested env:');
        $this->line(sprintf('SPORK_INGEST_TOKEN=%s', $apiKey));
        $this->line('SPORK_INGEST_URL=https://echo.kregel.dev/api/infrastructure/monitor/ingest');
        $this->newLine();

        return self::SUCCESS;
    }

    private function resolveUser(): ?User
    {
        $userId = $this->option('user-id');

        if (! empty($userId)) {
            return User::query()->whereKey((int) $userId)->first();
        }

        $email = $this->option('email');

        if (! empty($email)) {
            return User::query()->where('email', (string) $email)->first();
        }

        $count = User::query()->count();

        if ($count === 1) {
            return User::query()->first();
        }

        return null;
    }
}
