<?php

declare(strict_types=1);

namespace App\Console\Commands\Infrastructure;

use App\Models\Credential;
use App\Models\User;
use Illuminate\Console\Command;

class CreateMonitorDashboardCredentialCommand extends Command
{
    protected $signature = 'infrastructure:monitor-dashboard-credential
        {--user-id= : User id that should own the credential}
        {--email= : User email that should own the credential}
        {--name=Monitor Dashboard : Credential display name}
        {--token-url= : OAuth token endpoint URL (overrides auto-discovery from OIDC_ISSUER)}
        {--client-id= : OAuth client_id (overrides OIDC_CLIENT_ID)}
        {--client-secret= : OAuth client_secret (overrides OIDC_CLIENT_SECRET)}
        {--scope= : OAuth scope (overrides OIDC_SCOPES)}
        {--audience= : OAuth audience (optional)}
        {--force : Update existing credential if it exists}';

    protected $description = 'Create or update a monitor-dashboard credential with OAuth client credentials.
This credential is used by the bridge to mint a JWT token (via OAuth client_credentials flow) 
which it uses to authenticate with the monitor server WebSocket API (wss://monitor.../dashboard).
Reads from env: OIDC_CLIENT_ID, OIDC_CLIENT_SECRET, OIDC_ISSUER, OIDC_SCOPES

Note: This is separate from the monitor-bridge credential (simple API key for Spork authentication).';

    public function handle(): int
    {
        $user = $this->resolveUser();

        if (! $user) {
            $this->error('Unable to resolve user. Provide --user-id= or --email=');

            return self::FAILURE;
        }

        // Read from env with command-line overrides
        $clientId = $this->option('client-id') ?: env('OIDC_CLIENT_ID');
        $clientSecret = $this->option('client-secret') ?: env('OIDC_CLIENT_SECRET');
        $issuer = env('OIDC_ISSUER');
        $scope = $this->option('scope') ?: env('OIDC_SCOPES');
        $audience = $this->option('audience');

        // Get token URL - either from option, or discover from well-known config
        $tokenUrl = $this->option('token-url');
        if (! $tokenUrl && $issuer) {
            $tokenUrl = $this->discoverTokenEndpoint($issuer);
        }

        if (! $tokenUrl || ! $clientId || ! $clientSecret) {
            $this->error('Missing required OAuth parameters.');
            $this->line('');
            $this->line('Required environment variables:');
            $this->line('  OIDC_CLIENT_ID');
            $this->line('  OIDC_CLIENT_SECRET');
            $this->line('  OIDC_ISSUER (used to discover token endpoint via .well-known/openid-configuration)');
            $this->line('');
            $this->line('Optional:');
            $this->line('  OIDC_SCOPES');
            $this->line('');
            $this->line('Or provide via command-line options: --token-url, --client-id, --client-secret');

            return self::FAILURE;
        }

        $existing = Credential::query()
            ->where('service', 'monitor-dashboard')
            ->first();

        if ($existing && ! $this->option('force')) {
            $this->error('A monitor-dashboard credential already exists. Use --force to update it.');
            $this->line(sprintf('Existing credential ID: %s', $existing->id));
            $this->line(sprintf('Owner: %s (%s)', $existing->user->email ?? 'unknown', $existing->user_id));

            return self::FAILURE;
        }

        $settings = [
            'oauth_token_url' => $tokenUrl,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ];

        if ($scope) {
            $settings['scope'] = $scope;
        }

        if ($audience) {
            $settings['audience'] = $audience;
        }

        if ($existing && $this->option('force')) {
            $existing->update([
                'user_id' => $user->id,
                'name' => (string) $this->option('name'),
                'api_key' => $clientId,
                'secret_key' => $clientSecret,
                'settings' => $settings,
                'access_token' => null, // Clear existing token to force refresh
            ]);

            $credential = $existing->refresh();

            $this->newLine();
            $this->info('Monitor dashboard credential updated.');
        } else {
            $credential = Credential::query()->create([
                'user_id' => $user->id,
                'type' => Credential::TYPE_DEVELOPMENT,
                'service' => 'monitor-dashboard',
                'name' => (string) $this->option('name'),
                'api_key' => $clientId,
                'secret_key' => $clientSecret,
                'settings' => $settings,
            ]);

            $this->newLine();
            $this->info('Monitor dashboard credential created.');
        }

        $this->line(sprintf('User: %s (%s)', $user->email, $user->id));
        $this->line(sprintf('Credential id: %s', $credential->id));
        $this->line(sprintf('Service: %s', $credential->service));
        $this->line(sprintf('Token URL: %s', $tokenUrl));
        $this->line(sprintf('Client ID: %s', $clientId));
        if ($scope) {
            $this->line(sprintf('Scope: %s', $scope));
        }
        if ($audience) {
            $this->line(sprintf('Audience: %s', $audience));
        }
        $this->newLine();

        $this->info('The bridge will automatically mint a JWT token using these OAuth credentials.');
        $this->info('The token will be stored in the access_token field and refreshed as needed.');
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

    /**
     * Discover the OAuth token endpoint from OpenID Connect well-known configuration.
     */
    private function discoverTokenEndpoint(string $issuer): ?string
    {
        $wellKnownUrl = rtrim($issuer, '/').'/.well-known/openid-configuration';

        $this->line(sprintf('Discovering token endpoint from: %s', $wellKnownUrl));

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)->get($wellKnownUrl);

            if (! $response->successful()) {
                $this->warn(sprintf('Failed to fetch well-known config: HTTP %s', $response->status()));

                return null;
            }

            $config = $response->json();

            if (! isset($config['token_endpoint'])) {
                $this->warn('Well-known config missing token_endpoint');

                return null;
            }

            $tokenEndpoint = $config['token_endpoint'];
            $this->info(sprintf('Discovered token endpoint: %s', $tokenEndpoint));

            return $tokenEndpoint;
        } catch (\Throwable $e) {
            $this->warn(sprintf('Failed to discover token endpoint: %s', $e->getMessage()));

            return null;
        }
    }
}
