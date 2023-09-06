<?php

namespace App\Http\Controllers\Spork;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PeopleController extends Controller
{
    public function __invoke() {
        return Inertia::render('People', []);
    }
}
