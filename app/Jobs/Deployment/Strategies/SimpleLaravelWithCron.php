<?php

declare(strict_types=1);

namespace App\Jobs\Deployment\Strategies;

use App\Models\Credential;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laravel\Forge\Forge;

class SimpleLaravelWithCron implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        protected string $redirectTo,
        protected string $domain,
        protected int $server,
        protected Credential $credential
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $client = new Forge($this->credential->access_token, new Client([
            'base_uri' => 'https://forge.laravel.com/api/v1/',
            'http_errors' => false,
            'headers' => [
                'Authorization' => 'Bearer '.$this->credential->access_token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]));
        $site = $client->createSite($this->server, [
            'name' => $this->domain,
            'project_type' => 'static',
            'directory' => '/',
        ]);

        $client->createRedirectRule($this->server, $site->id, [
            'from' => '/',
            'to' => $this->redirectTo,
            'type' => 'redirect',
            'code' => 301,
        ]);
    }
}
