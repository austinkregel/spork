<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCredentialRequest;
use App\Models\User;
use Inertia\Inertia;

class CredentialController extends Controller
{
    public function store(
        StoreCredentialRequest $request,
    ) {
        /** @var User $user */
        $user = $request->user();

        $credential = $user->credentials()->create($request->validated());

        return Inertia::location(route('manage.show', ['link' => $credential->getTable()]));
    }
}
