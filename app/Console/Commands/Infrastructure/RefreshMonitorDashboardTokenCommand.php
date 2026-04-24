<?php

declare(strict_types=1);

namespace App\Console\Commands\Infrastructure;

use App\Models\Credential;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class RefreshMonitorDashboardTokenCommand extends Command
{
    protected $signature = 'infrastructure:monitor-dashboard-refresh-token
        {--credential-id= : Specific credential ID to refresh (defaults to first monitor-dashboard credential)}';

    protected $description = 'Manually refresh the OAuth JWT token for the monitor-dashboard credential.';

    public function handle(): int
    {
        $credentialId = $this->option('credential-id');

        $credential = $credentialId
            ? Credential::query()->where('service', 'monitor-dashboard')->whereKey($credentialId)->first()
            : Credential::query()->where('service', 'monitor-dashboard')->first();

        if (! $credential) {
            $this->error('No monitor-dashboard credential found. Create one with: infrastructure:monitor-dashboard-credential');

            return self::FAILURE;
        }

        $settings = $credential->settings ?? [];
        $tokenUrl = $settings['oauth_token_url'] ?? $settings['token_url'] ?? null;
        $clientId = $settings['client_id'] ?? $credential->api_key ?? null;
        $clientSecret = $settings['client_secret'] ?? $credential->secret_key ?? null;

        if (! $tokenUrl || ! $clientId || ! $clientSecret) {
            $this->error('Credential is missing required OAuth configuration.');
            $this->line('Required: oauth_token_url (or token_url), client_id, client_secret');

            return self::FAILURE;
        }

        $this->info('Minting OAuth token...');
        $this->line(sprintf('Token URL: %s', $tokenUrl));
        $this->line(sprintf('Client ID: %s', $clientId));

        try {
            $response = Http::asForm()->post($tokenUrl, [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => $settings['scope'] ?? null,
                'audience' => $settings['audience'] ?? $settings['aud'] ?? null,
            ]);

            if (! $response->successful()) {
                $this->error('Token minting failed');
                $this->line(sprintf('Status: %s', $response->status()));
                $this->line(sprintf('Response: %s', $response->body()));

                return self::FAILURE;
            }

            $data = $response->json();
            $accessToken = $data['access_token'] ?? null;

            if (! $accessToken) {
                $this->error('Token response missing access_token');
                $this->line('Response: '.$response->body());

                return self::FAILURE;
            }

            $expiresIn = $data['expires_in'] ?? null;
            $tokenType = $data['token_type'] ?? null;

            $credential->update([
                'access_token' => $accessToken,
                'settings' => array_merge($settings, [
                    'last_token_refreshed_at' => now()->toIso8601String(),
                    'last_token_expires_in' => $expiresIn,
                    'last_token_type' => $tokenType,
                ]),
            ]);

            $this->newLine();
            $this->info('Token refreshed successfully!');
            $this->line(sprintf('Credential ID: %s', $credential->id));
            $this->line(sprintf('Token Type: %s', $tokenType ?? 'unknown'));
            if ($expiresIn) {
                $this->line(sprintf('Expires In: %s seconds', $expiresIn));
                $this->line(sprintf('Expires At: %s', now()->addSeconds($expiresIn)->toDateTimeString()));
            }
            $this->newLine();
            $this->line('The bridge will use this token on its next connection attempt.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Token minting failed with exception');
            $this->line(sprintf('Error: %s', $e->getMessage()));

            return self::FAILURE;
        }
    }
}
