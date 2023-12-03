<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class OnlyHost
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user(), 404);

        abort_unless(in_array($request->user()->email, config('auth.admin_emails')), 404);

        return $next($request);
    }
}
