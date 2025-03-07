<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use Inertia\Inertia;

class DomainsController extends Controller
{
    public function show(Domain $domain)
    {
        $domain->load('records');

        return Inertia::render('Domain', [
            'domain' => $domain,
        ]);
    }
}
