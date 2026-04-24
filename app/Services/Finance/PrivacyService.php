<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Contracts\Services\Finance\PrivacyServiceContract;
use App\Models\Credential;
use Illuminate\Support\Arr;

class PrivacyService implements PrivacyServiceContract
{
    public function __construct(
        protected PrivacyHttpService $http,
    ) {}

    public function listTransactions(
        Credential $credential,
        int $page = 1,
        int $pageSize = 50,
        ?string $result = null,
        ?string $begin = null,
        ?string $end = null,
        ?string $accountToken = null,
        ?string $cardToken = null,
    ): array {
        $query = array_filter([
            'page' => max(1, $page),
            'page_size' => max(1, min($pageSize, 1000)),
            'result' => $result,
            'begin' => $begin,
            'end' => $end,
            'account_token' => $accountToken,
            'card_token' => $cardToken,
        ], fn ($value) => $value !== null && $value !== '');

        $path = '/v1/transactions?'.http_build_query($query);

        $response = $this->http
            ->auth((string) $credential->api_key)
            ->get($path)
            ->toArray();

        // Normalize to an array with a stable `data` key even if Privacy changes the envelope.
        if (! array_key_exists('data', $response)) {
            return [
                'data' => Arr::isAssoc($response) ? [] : $response,
                'raw' => $response,
            ];
        }

        return $response;
    }

    public function listCards(
        Credential $credential,
        int $page = 1,
        int $pageSize = 50,
    ): array {
        $query = array_filter([
            'page' => max(1, $page),
            'page_size' => max(1, min($pageSize, 1000)),
        ], fn ($value) => $value !== null && $value !== '');

        $path = '/v1/cards?'.http_build_query($query);

        $response = $this->http
            ->auth((string) $credential->api_key)
            ->get($path)
            ->toArray();

        if (! array_key_exists('data', $response)) {
            return [
                'data' => Arr::isAssoc($response) ? [] : $response,
                'raw' => $response,
            ];
        }

        return $response;
    }
}
