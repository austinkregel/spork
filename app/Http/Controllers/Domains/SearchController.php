<?php

declare(strict_types=1);

namespace App\Http\Controllers\Domains;

use App\Http\Controllers\Controller;
use App\Models\Credential;
use App\Services\Factories\RegistrarServiceFactory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    public function __invoke(Request $request, RegistrarServiceFactory $factory)
    {
        $query = (string) $request->input('q', '');
        $results = null;

        if ($query !== '') {
            $credential = Credential::query()
                ->where('type', Credential::TYPE_REGISTRAR)
                ->first();

            if ($credential) {
                $service = $factory->make($credential);

                $results = $service->searchDomain($query);
            }
        }

        return Inertia::render('Domains/Search', [
            'query' => $query,
            'results' => $results,
        ]);
    }
}
