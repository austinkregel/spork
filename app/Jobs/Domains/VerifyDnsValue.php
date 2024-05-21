<?php

declare(strict_types=1);

namespace App\Jobs\Domains;

use App\Events\Domains\DnsRecordVerified;
use App\Models\User;
use App\Notifications\DnsVerificationSuccessful;
use Illuminate\Contracts\Queue\ShouldQueue;

class VerifyDnsValue implements ShouldQueue
{
    public function __construct(
        public string $host,
        public string $type,
        public string|array $expectedValue,
        protected bool $relaunchDnsCheckAfterDelay = false,
    ) {
    }

    public function handle(): void
    {
        $value = array_map(fn (array $record) => $record['ip'], dns_get_record($this->host, DNS_A));

        if ($this->relaunchDnsCheckAfterDelay && ! in_array($this->expectedValue, $value)) {
            dispatch(new static($this->host, $this->type, $this->expectedValue))->delay(5 * 60);

            return;
        }

        event(new DnsRecordVerified(
            $this->host,
            $this->expectedValue
        ));

        User::first()->notify(new DnsVerificationSuccessful(
            $this->host,
            $this->type,
            $this->expectedValue,
        ));
    }
}
