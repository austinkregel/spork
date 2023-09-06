<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use Inertia\Inertia;

class DomainsController extends Controller
{
    public function __invoke()
    {
        return Inertia::render('Domains', []);
    }

    public function show(Domain $domain)
    {
        return Inertia::render('Domains', [
            'domain' => $domain,
        ]);
    }
}
