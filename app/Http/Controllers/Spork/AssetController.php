<?php

namespace App\Http\Controllers\Spork;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssetController extends Controller
{
    public function index()
    {
        return Inertia::render('Assets/Index', [
            ''
        ]);
    }

    public function labels()
    {

    }
}
