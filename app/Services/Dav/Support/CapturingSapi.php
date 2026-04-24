<?php

declare(strict_types=1);

namespace App\Services\Dav\Support;

use Sabre\HTTP\ResponseInterface;
use Sabre\HTTP\Sapi;

/**
 * Sabre Sapi replacement that swallows the call to `sendResponse` so we can
 * convert the populated httpResponse into a Symfony response after the fact.
 */
class CapturingSapi extends Sapi
{
    public static function sendResponse(ResponseInterface $response)
    {
        // Intentionally a no-op; the controller flushes the response itself.
    }
}
