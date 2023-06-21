<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCredentialRequest;
use App\Http\Requests\UpdateCredentialRequest;
use App\Models\Credential;

class CredentialController extends Controller
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
    public function store(StoreCredentialRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Credential $credential)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCredentialRequest $request, Credential $credential)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Credential $credential)
    {
        //
    }
}
