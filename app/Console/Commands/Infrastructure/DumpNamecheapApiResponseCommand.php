<?php

declare(strict_types=1);

namespace App\Console\Commands\Infrastructure;

use App\Models\Credential;
use App\Services\Registrar\NamecheapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DumpNamecheapApiResponseCommand extends Command
{
    protected $signature = 'infrastructure:namecheap:dump
        {--credential-id= : Credential id for the Namecheap credential}
        {--api-command= : Namecheap API command, e.g. namecheap.domains.getList}
        {--params= : JSON object of extra query params to include}
        {--output= : Output file path for raw XML (defaults to storage/app/namecheap-dump.xml)}';

    protected $description = 'Manually call a Namecheap API command and dump the raw XML response to a file (for debugging; not for tests).';

    public function handle(): int
    {
        $credentialId = $this->option('credential-id');
        if (empty($credentialId)) {
            $this->error('Missing --credential-id=');

            return self::FAILURE;
        }

        $credential = Credential::query()->find((int) $credentialId);
        if (! $credential) {
            $this->error('Credential not found.');

            return self::FAILURE;
        }

        if ($credential->service !== Credential::NAMECHEAP) {
            $this->error(sprintf('Credential service must be "%s". Got "%s".', Credential::NAMECHEAP, (string) $credential->service));

            return self::FAILURE;
        }

        $apiCommand = (string) ($this->option('api-command') ?? '');
        if ($apiCommand === '') {
            $this->error('Missing --api-command=');

            return self::FAILURE;
        }

        $extraParams = $this->decodeParams((string) ($this->option('params') ?? ''));

        $query = array_merge([
            'ApiUser' => $credential->settings['api_user'],
            'ApiKey' => $credential->access_token,
            'UserName' => $credential->settings['username'],
            'ClientIp' => $credential->settings['client_ip'],
            'Command' => $apiCommand,
        ], $extraParams);

        $url = NamecheapService::NAMECHEAP_URL.'?'.http_build_query($query);

        $xml = Http::timeout(30)->retry(2, 250)->get($url)->body();

        $output = (string) ($this->option('output') ?: storage_path('app/namecheap-dump.xml'));
        @mkdir(dirname($output), 0777, true);

        if (file_put_contents($output, $xml) === false) {
            throw new RuntimeException('Unable to write output file: '.$output);
        }

        $this->info('Saved.');
        $this->line($output);

        return self::SUCCESS;
    }

    /**
     * @return array<string, scalar|null>
     */
    private function decodeParams(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $data = json_decode($raw, true);
        if (! is_array($data)) {
            throw new RuntimeException('--params must be a JSON object.');
        }

        return $data;
    }
}


