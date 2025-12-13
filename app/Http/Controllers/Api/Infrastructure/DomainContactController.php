<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Infrastructure;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Infrastructure\DomainContactRequest;
use App\Http\Resources\Infrastructure\DomainContactResource;
use App\Models\Domain;
use App\Models\DomainContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DomainContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $domainIds = $request->user()
            ->domains()
            ->pluck('domains.id')
            ->unique()
            ->values();

        $contacts = DomainContact::query()
            ->whereIn('domain_id', $domainIds)
            ->latest()
            ->get();

        return response()->json([
            'contacts' => DomainContactResource::collection($contacts),
        ]);
    }

    public function store(DomainContactRequest $request, Domain $domain): JsonResponse
    {
        $contact = $domain->contacts()->create($request->validated());

        return response()->json([
            'contact' => new DomainContactResource($contact),
        ], 201);
    }

    public function update(DomainContactRequest $request, Domain $domain, DomainContact $contact): JsonResponse
    {
        abort_if($contact->domain_id !== $domain->id, 404);

        $contact->update($request->validated());

        return response()->json([
            'contact' => new DomainContactResource($contact->fresh()),
        ]);
    }

    public function destroy(Domain $domain, DomainContact $contact): JsonResponse
    {
        abort_if($contact->domain_id !== $domain->id, 404);

        $contact->delete();

        return response()->json([
            'status' => 'deleted',
        ]);
    }
}

