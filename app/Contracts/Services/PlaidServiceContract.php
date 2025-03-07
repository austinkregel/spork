<?php

/**
 * PlaidService
 */
declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\Finance\Account;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Support\Collection;

interface PlaidServiceContract
{
    public function getInstitutionsByName(string $bankName): array;

    public function getInstitutionsById(string $bankId): Collection;

    public function getTransactions(string $accessToken, Carbon $startDate, Carbon $endDate): Collection;

    public function getAccessToken(string $publicToken): array;

    public function getAccounts(string $accessToken): array;

    public function rotateAccessTokens(Account $account): Account;

    public function getCategories(): array;

    public function getInstitutions(int $count = 500, int $page = 1): LengthAwarePaginatorContract;

    public function createLinkToken(string $userId): array;

    public function updateLinkToken(string $userId, string $accessToken): array;

    public function updateWebhook(string $access_token): array;
}
