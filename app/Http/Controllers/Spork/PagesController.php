<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class PagesController extends Controller
{
    public function __invoke()
    {
        return Inertia::render('Pages', []);
    }

    public function create()
    {
        return Inertia::render('Pages/Create', []);
    }
}
