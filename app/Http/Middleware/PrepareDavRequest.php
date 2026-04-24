<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures DAV requests can be replayed (php://input is not always rewindable
 * inside Laravel) and short-circuits any automatic JSON / form parsing that
 * Laravel might perform on the body.
 */
class PrepareDavRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        // Force Laravel to keep the raw body around for the Sabre server.
        $request->getContent();

        return $next($request);
    }
}
