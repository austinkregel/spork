<?php

declare(strict_types=1);

namespace App\Jobs\Finance;

use App\Contracts\Services\Finance\PrivacyServiceContract;
use App\Models\Credential;
use App\Models\Finance\PrivacyCard;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;

class SyncPrivacyCardsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array{page_size?: int|null}  $options
     */
    public function __construct(
        public Credential $credential,
        public array $options = [],
    ) {}

    public function handle(PrivacyServiceContract $privacy): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $pageSize = (int) ($this->options['page_size'] ?? 50);
        $pageSize = max(1, min($pageSize, 1000));

        $page = 1;
        while (true) {
            $response = $privacy->listCards(
                credential: $this->credential,
                page: $page,
                pageSize: $pageSize,
            );

            $items = Arr::get($response, 'data');
            if (! is_array($items) || Arr::isAssoc($items) || $items === []) {
                break;
            }

            $this->upsertCards(array_values(array_filter($items, 'is_array')));

            if (count($items) < $pageSize) {
                break;
            }

            $page++;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function upsertCards(array $items): void
    {
        $rows = [];

        foreach ($items as $item) {
            $token = Arr::get($item, 'token') ?? Arr::get($item, 'card_token');
            if (! is_string($token) || $token === '') {
                continue;
            }

            $rows[] = [
                'credential_id' => $this->credential->id,
                'card_token' => $token,
                'state' => is_string(Arr::get($item, 'state')) ? Arr::get($item, 'state') : null,
                'type' => is_string(Arr::get($item, 'type')) ? Arr::get($item, 'type') : null,
                'memo' => is_string(Arr::get($item, 'memo')) ? Arr::get($item, 'memo') : null,
                'descriptor' => is_string(Arr::get($item, 'descriptor')) ? Arr::get($item, 'descriptor') : null,
                'spend_limit_cents' => is_numeric(Arr::get($item, 'spend_limit')) ? (int) Arr::get($item, 'spend_limit') : null,
                'spend_limit_duration' => is_string(Arr::get($item, 'spend_limit_duration')) ? Arr::get($item, 'spend_limit_duration') : null,
                'data' => json_encode($item),
                'updated_at' => now(),
                'created_at' => now(),
            ];
        }

        if ($rows === []) {
            return;
        }

        PrivacyCard::query()->upsert(
            $rows,
            ['credential_id', 'card_token'],
            [
                'state',
                'type',
                'memo',
                'descriptor',
                'spend_limit_cents',
                'spend_limit_duration',
                'data',
                'updated_at',
            ]
        );
    }
}
