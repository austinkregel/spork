<?php

declare(strict_types=1);

namespace App\Jobs\Infrastructure;

use App\Models\DnsZone;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ApplyDnsZoneChangesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $dnsZoneId,
        public int $userId,
        public array $changes,
    ) {
    }

    public function handle(): void
    {
        $zone = DnsZone::query()
            ->whereKey($this->dnsZoneId)
            ->where('user_id', $this->userId)
            ->first();

        if (! $zone) {
            return;
        }

        $settings = $zone->settings ?? [];
        $pending = $settings['pending_changes'] ?? [];
        $settings['pending_changes'] = array_values(array_merge($pending, $this->changes));

        $zone->update(['settings' => $settings]);
    }
}















