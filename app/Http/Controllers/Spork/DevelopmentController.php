<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spork;

use Inertia\Inertia;

class DevelopmentController
{
    public function index()
    {
        return Inertia::render('Development/Index', [
            //            'instances' => LaravelProgrammingStyle::instancesOf(CustomAction::class),
        ]);
    }
}
