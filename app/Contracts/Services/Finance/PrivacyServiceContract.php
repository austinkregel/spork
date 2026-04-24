<?php

declare(strict_types=1);

namespace App\Contracts\Services\Finance;

use App\Models\Credential;

interface PrivacyServiceContract
{
    /**
     * List Privacy transactions.
     *
     * Expected endpoint: GET https://api.privacy.com/v1/transactions
     * Auth: Authorization: api-key <token>
     *
     * @return array<string, mixed>
     */
    public function listTransactions(
        Credential $credential,
        int $page = 1,
        int $pageSize = 50,
        ?string $result = null,
        ?string $begin = null,
        ?string $end = null,
        ?string $accountToken = null,
        ?string $cardToken = null,
    ): array;

    /**
     * List Privacy cards.
     *
     * Expected endpoint: GET https://api.privacy.com/v1/cards
     * Auth: Authorization: api-key <token>
     *
     * @return array<string, mixed>
     */
    public function listCards(
        Credential $credential,
        int $page = 1,
        int $pageSize = 50,
    ): array;
}
