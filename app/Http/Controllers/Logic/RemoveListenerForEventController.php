<?php

declare(strict_types=1);

namespace App\Http\Controllers\Logic;

use App\Http\Controllers\Controller;
use App\Providers\EventServiceProvider;
use App\Services\Code;
use App\Services\Programming\LaravelProgrammingStyle;
use Illuminate\Http\Request;

class RemoveListenerForEventController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'event' => 'required',
            'listener' => 'required',
        ]);

        LaravelProgrammingStyle::for(EventServiceProvider::class)
            ->removeListenerFromEvent($request->get('event'), $request->get('listener'))
            ->toFile(Code::REPLACE_FILE);
    }
}
