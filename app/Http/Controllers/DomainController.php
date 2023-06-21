<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDomainRequest;
use App\Http\Requests\UpdateDomainRequest;
use App\Models\Domain;

class DomainController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDomainRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Domain $domain)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDomainRequest $request, Domain $domain)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Domain $domain)
    {
        //
    }
}
