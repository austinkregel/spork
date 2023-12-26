<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Plaid;

use App\Contracts\Services\PlaidServiceContract;
use App\Http\Controllers\Controller;
use App\Models\Credential;
use App\Models\User;
use Illuminate\Http\Request;

class ExchangeTokenController extends Controller
{
    public function __invoke(Request $request)
    {
        $this->validate($request, [
            'institution' => 'required',
            'public_token' => 'required',
        ]);

        $service = app(PlaidServiceContract::class);
        $exchangedToken = $service->getAccessToken((string) $request->get('public_token'));

        /** @var User $user */
        $user = auth()->user();

        $institution = $service->getInstitutionsById($request->get('institution'));
        $token = $user->credentials()->firstOrCreate([
            'name' => $institution->get('name'),
            'type' => Credential::TYPE_FINANCE,
            'service' => 'plaid',
            'settings' => [
                'institution' => $request->get('institution'),
                'item_id' => $exchangedToken['item_id'],
            ],
        ], [
            'api_key' => $exchangedToken['access_token'],
            'access_token' => $exchangedToken['access_token'],
        ]);

        return $token;
    }
}
