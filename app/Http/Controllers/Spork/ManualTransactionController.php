<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Http\Requests\Banking\StoreManualTransactionRequest;
use App\Services\Finance\ManualTransactionService;
use Illuminate\Http\RedirectResponse;

class ManualTransactionController
{
    public function __construct(
        protected ManualTransactionService $manualTransactionService,
    ) {}

    public function store(StoreManualTransactionRequest $request): RedirectResponse
    {
        $this->manualTransactionService->store($request->user(), $request->validated());

        return back()->with('flash.banner', 'Manual transaction added.');
    }
}





