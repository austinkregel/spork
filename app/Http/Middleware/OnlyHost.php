<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OnlyHost
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user(), 404);

        abort_unless(in_array($request->user()->email, config('auth.admin_emails')), 404);

        return $next($request);
    }
}
