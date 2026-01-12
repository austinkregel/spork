<?php

declare(strict_types=1);

namespace App\Jobs\Finance;

use App\Contracts\Services\Finance\PrivacyServiceContract;
use App\Models\Credential;
use App\Models\Finance\PrivacyTransaction;
use App\Services\Finance\PrivacyTransactionTagger;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;

class SyncPrivacyTransactionsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array{begin?: string|null, end?: string|null, page_size?: int|null, results?: string[]|null}  $options
     */
    public function __construct(
        public Credential $credential,
        public array $options = [],
    ) {}

    public function handle(PrivacyServiceContract $privacy, PrivacyTransactionTagger $tagger): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $pageSize = (int) ($this->options['page_size'] ?? 50);
        $pageSize = max(1, min($pageSize, 1000));

        [$begin, $end] = $this->resolveDateRange();

        $results = $this->resolveResults();

        foreach ($results as $result) {
            $this->syncResultSet(
                privacy: $privacy,
                tagger: $tagger,
                begin: $begin,
                end: $end,
                pageSize: $pageSize,
                result: $result,
            );
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function resolveDateRange(): array
    {
        $now = CarbonImmutable::now('UTC');

        $begin = $this->options['begin'] ?? null;
        $end = $this->options['end'] ?? null;

        if (is_string($begin) && $begin !== '' && is_string($end) && $end !== '') {
            return [$begin, $end];
        }

        // Default: last 14 days, inclusive of today. Privacy's `end` is exclusive, so add one day.
        $defaultBegin = $now->subDays(14)->toDateString();
        $defaultEndExclusive = $now->addDay()->toDateString();

        return [$defaultBegin, $defaultEndExclusive];
    }

    /**
     * @return array<int, string>
     */
    protected function resolveResults(): array
    {
        $results = $this->options['results'] ?? null;

        if (is_array($results) && $results !== []) {
            return array_values(array_filter(array_map('strval', $results)));
        }

        // Privacy v1 docs show APPROVED / DECLINED. If we want \"all\", fetch both.
        return ['APPROVED', 'DECLINED'];
    }

    protected function syncResultSet(
        PrivacyServiceContract $privacy,
        PrivacyTransactionTagger $tagger,
        string $begin,
        string $end,
        int $pageSize,
        string $result,
    ): void {
        $page = 1;

        while (true) {
            $response = $privacy->listTransactions(
                credential: $this->credential,
                page: $page,
                pageSize: $pageSize,
                result: $result,
                begin: $begin,
                end: $end,
            );

            $items = $this->extractTransactions($response);

            if ($items === []) {
                break;
            }

            $this->upsertTransactions($items);
            $this->applyAutomaticTags($tagger, $items);

            if (count($items) < $pageSize) {
                break;
            }

            $page++;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function applyAutomaticTags(PrivacyTransactionTagger $tagger, array $items): void
    {
        $user = $this->credential->user;

        if (! $user) {
            return;
        }

        $privacyIds = [];

        foreach ($items as $item) {
            $privacyId = $this->extractPrivacyTransactionId($item);

            if ($privacyId !== null) {
                $privacyIds[] = $privacyId;
            }
        }

        $privacyIds = array_values(array_unique(array_filter($privacyIds)));

        if ($privacyIds === []) {
            return;
        }

        PrivacyTransaction::query()
            ->where('credential_id', $this->credential->id)
            ->whereIn('privacy_transaction_id', $privacyIds)
            ->get()
            ->each(fn (PrivacyTransaction $transaction) => $tagger->applyAutomaticTags($user, $transaction));
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<int, array<string, mixed>>
     */
    protected function extractTransactions(array $response): array
    {
        $data = Arr::get($response, 'data');

        if (! is_array($data)) {
            return [];
        }

        // Some APIs return `data` as an object; only accept a list here.
        if (Arr::isAssoc($data)) {
            return [];
        }

        return array_values(array_filter($data, 'is_array'));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function upsertTransactions(array $items): void
    {
        $rows = [];

        foreach ($items as $item) {
            $privacyId = $this->extractPrivacyTransactionId($item);

            if ($privacyId === null) {
                continue;
            }

            $amount = Arr::get($item, 'amount');
            $amountCents = $this->normalizeCents($amount);

            $merchant = Arr::get($item, 'merchant', []);
            if (! is_array($merchant)) {
                $merchant = [];
            }

            $created = $this->dateOrNull(Arr::get($item, 'created'));
            $status = $this->stringOrNull(Arr::get($item, 'status') ?? Arr::get($item, 'statusDescription'));

            $rows[] = [
                'credential_id' => $this->credential->id,
                'privacy_transaction_id' => $privacyId,
                'result' => $this->stringOrNull(Arr::get($item, 'result')),
                'status' => $status,
                'amount_cents' => $amountCents,
                // Prefer v1 fields.
                'currency_code' => $this->stringOrNull(Arr::get($item, 'merchant_currency') ?? Arr::get($item, 'currency_code') ?? Arr::get($item, 'currency')),

                // Use created as our authorized date (closest reliable timestamp we have in v1).
                'date_authorized' => $created,

                // Only set settled timestamp when the transaction is settled; Privacy v1 `end` is based on created.
                'date_settled' => ($status === 'SETTLED') ? $created : null,

                // Most usable user-facing fields live under merchant.
                'descriptor' => $this->stringOrNull(Arr::get($merchant, 'descriptor') ?? Arr::get($item, 'descriptor')),
                'memo' => $this->stringOrNull(Arr::get($merchant, 'descriptor') ?? Arr::get($item, 'memo')),
                'mcc' => $this->stringOrNull(Arr::get($merchant, 'mcc') ?? Arr::get($item, 'mcc')),
                'card_uuid' => $this->stringOrNull(Arr::get($item, 'card_token') ?? Arr::get($item, 'cardUuid')),
                'card_id' => $this->intOrNull(Arr::get($item, 'card_id') ?? Arr::get($item, 'cardID')),
                // Eloquent upsert bypasses casting; store JSON explicitly.
                'data' => json_encode($item),
                'updated_at' => now(),
                'created_at' => now(),
            ];
        }

        if ($rows === []) {
            return;
        }

        PrivacyTransaction::query()->upsert(
            $rows,
            ['credential_id', 'privacy_transaction_id'],
            [
                'result',
                'status',
                'amount_cents',
                'currency_code',
                'date_authorized',
                'date_settled',
                'descriptor',
                'memo',
                'mcc',
                'card_uuid',
                'card_id',
                'data',
                'updated_at',
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function extractPrivacyTransactionId(array $item): ?string
    {
        $candidates = [
            Arr::get($item, 'token'),
            Arr::get($item, 'transaction_token'),
            Arr::get($item, 'transactionToken'),
            Arr::get($item, 'transaction_uuid'),
            Arr::get($item, 'transactionUuid'),
            Arr::get($item, 'transaction_id'),
            Arr::get($item, 'transactionID'),
            Arr::get($item, 'id'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }

            if (is_int($candidate) || is_float($candidate)) {
                return (string) $candidate;
            }
        }

        // Fall back to a stable hash if we have enough fields (avoid dropping data entirely).
        $descriptor = $this->stringOrNull(Arr::get($item, 'descriptor'));
        $created = $this->stringOrNull(Arr::get($item, 'created'));
        $amount = $this->normalizeCents(Arr::get($item, 'amount'));

        if ($descriptor && $created && $amount !== null) {
            return 'derived-'.sha1($descriptor.'|'.$created.'|'.$amount);
        }

        return null;
    }

    protected function normalizeCents(mixed $amount): ?int
    {
        if ($amount === null) {
            return null;
        }

        if (is_int($amount)) {
            return $amount;
        }

        if (is_numeric($amount)) {
            $value = (float) $amount;

            // If it's an integer-ish numeric string (e.g. \"3396\"), treat as cents.
            if (floor($value) === $value && abs($value) >= 100) {
                return (int) $value;
            }

            return (int) round($value * 100);
        }

        return null;
    }

    protected function dateOrNull(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    protected function intOrNull(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }
}
