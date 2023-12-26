<?php

declare(strict_types=1);

namespace App\Actions\Spork\Domains;

use App\Contracts\ActionInterface;

class SyncNamecheapDnsAction implements ActionInterface
{
    public function __invoke()
    {
        request()->validate([
            'domains' => 'required|array',
            'nameservers' => 'required',
        ]);

        $domains = request()->get('domains');

        $nameservers = explode(',', request()->get('nameservers', ''));

        foreach ($domains as $domain) {
            $this->service->updateDomainNs($domain, $nameservers);
        }

        return 'OK';
    }

    public function show(): bool
    {
        return auth()->check();
    }
}
