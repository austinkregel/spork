<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Contracts\Services\PlaidServiceContract;
use App\Models\Finance\Account;
use Carbon\Carbon;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PlaidService implements PlaidServiceContract
{
    protected PlaidHttpService $http;

    public function __construct(PlaidHttpService $httpService)
    {
        $this->http = $httpService;
    }

    public function getInstitutionsByName(string $bankName): array
    {
        return $this->http
            ->{config('services.plaid.env')}()
            ->auth([
                'public_key' => env('PLAID_PUBLIC_KEY'),
            ])
            ->post('/institutions/search', [
                'query' => $bankName,
                'products' => ['transactions'],
            ])
            ->get('institutions');
    }

    public function getInstitutionsById(string $bankId): Collection
    {
        return new Collection($this->http
            ->{config('services.plaid.env')}()
            ->auth([
                'public_key' => config('services.plaid.public_key'),
                'client_id' => config('services.plaid.client_id'),
                'secret' => config('services.plaid.secret_key'),
                'country_codes' => ['us']
            ])
            ->post('/institutions/get_by_id', [
                'institution_id' => $bankId,
                'options' => [
                    'include_optional_metadata' => true,
                ],
            ])
            ->get('institution'));
    }

    public function getTransactions(string $accessToken, Carbon $startDate, Carbon $endDate): Collection
    {
        throw_if(
            $startDate->gte($endDate),
            \InvalidArgumentException::class,
            sprintf(
                'Your start date of %s is after your end date of %s, which is illegal... You Can\'t do that you fool',
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            )
        );

        /// We should account for rate limiting here to make sure we don't make too many requests per second.
        $items = new Collection;
        $page = 1;
        do {
            $paginator = $this->getPaginator($accessToken, $startDate, $endDate, $page);

            $items = $items->concat($paginator->get('transactions'));
            $accounts = $paginator->get('accounts', []);

            $totalAvailable = count($paginator->get('transactions')) + ($page - 1) * 500;
            $total = $paginator->get('total_transactions');
            $loop = $total !== $totalAvailable;
            $page++;
        } while ($loop);

        return Collection::make([
            'transactions' => $items->toArray(),
            'accounts' => $accounts,
        ]);
    }

    protected function getPaginator(string $accessToken, Carbon $startDate, Carbon $endDate, int $page = 1)
    {
        throw_if(
            $startDate->gte($endDate),
            \InvalidArgumentException::class,
            sprintf(
                'Your paginator start date of %s is after your end date of %s, which is illegal... You Can\'t do that you fool',
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            )
        );
        /** @var Collection $paginator */
        try {
            return $this->http
                ->{config('services.plaid.env')}()
                ->auth([
                    'client_id' => config('services.plaid.client_id'),
                    'secret' => config('services.plaid.secret_key'),
                    'access_token' => $accessToken,
                ])
                ->post('/transactions/get', [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'options' => [
                        'count' => 500,
                        'offset' => ($page - 1) * 500,
                    ],
                ]);
        } catch (ClientException $exception) {
            if ($exception->getCode() === 400) {
                echo $exception->getMessage();

                return new Collection([
                    'transactions' => [],
                    'total_transactions' => 0,
                ]);
            }

            sleep(30);

            return $this->getPaginator($accessToken, $startDate, $endDate, $page);
        }
    }

    /**
     * Exchange the public token for a new access token
     *
     * @throws \Exception
     */
    public function getAccessToken(string $publicToken): array
    {
        return $this->http
            ->{config('services.plaid.env')}()
            ->auth([
                'public_token' => $publicToken,
                'client_id' => config('services.plaid.client_id'),
                'secret' => config('services.plaid.secret_key'),
            ])
            ->post('/item/public_token/exchange')
            ->toArray();
    }

    /**
     * Get the accounts for the token
     *
     * @throws \Exception
     */
    public function getAccounts(string $accessToken): array
    {
        return $this->http
            ->{config('services.plaid.env')}()
            ->auth([
                'client_id' => config('services.plaid.client_id'),
                'secret' => config('services.plaid.secret_key'),
                'access_token' => $accessToken,
            ])
            ->post('/accounts/get')
            ->toArray();
    }

    public function rotateAccessTokens(Account $account): Account
    {
        return $this->http
            ->{config('services.plaid.env')}()
            ->auth([
                'client_id' => config('services.plaid.client_id'),
                'secret' => config('services.plaid.secret_key'),
                'access_token' => $account->accessToken->token,
            ])
            ->post('/item/access_token/invalidate')
            ->toArray();
    }

    public function getCategories(): array
    {
        return $this->http
            ->{config('services.plaid.env')}()
            ->post('/categories/get', [])
            ->get('categories');
    }

    public function getInstitutions(int $count = 500, int $page = 1): LengthAwarePaginatorContract
    {
        $items = $this->http
            ->{config('services.plaid.env')}()
            ->auth([
                'client_id' => config('services.plaid.client_id'),
                'secret' => config('services.plaid.secret_key'),
                'count' => $count,
                'offset' => $count * ($page - 1),
            ])
            ->post('/institutions/get', [
                'options' => [
                    'include_optional_metadata' => true,
                ],
            ])
            ->toArray();

        return new LengthAwarePaginator($items['institutions'], $items['total'], $count, $page);
    }

    public function createLinkToken(string $userId): array
    {
        return $this->http
            ->{config('services.plaid.env')}()
            ->post('/link/token/create', [
                'client_id' => config('services.plaid.client_id'),
                'secret' => config('services.plaid.secret_key'),
                'client_name' => config('services.plaid.client_name'),
                'user' => [
                    'client_user_id' => $userId,
                ],
                'products' => config('services.plaid.products'),
                'country_codes' => config('services.plaid.country_codes'),
                'language' => config('services.plaid.language'),
            ])
            ->toArray();
    }

    public function updateLinkToken(string $userId, string $accessToken): array
    {
        return $this->http
            ->{config('services.plaid.env')}()
            ->post('/link/token/create', [
                'user' => [
                    'client_user_id' => $userId,
                ],
                'client_name' => config('services.plaid.client_name'),
                'country_codes' => config('services.plaid.country_codes'),
                'language' => config('services.plaid.language'),
                'client_id' => config('services.plaid.client_id'),
                'access_token' => $accessToken,
                'secret' => config('services.plaid.secret_key'),
            ])
            ->toArray();
    }

    public function updateWebhook(string $access_token): array
    {
        return $this->http
            ->{config('services.plaid.env')}()
            ->post('/item/webhook/update', [
                'access_token' => $access_token,
                'client_id' => config('services.plaid.client_id'),
                'secret' => config('services.plaid.secret_key'),
                'webhook' => route('webhook'),
            ])
            ->toArray();
    }
}
