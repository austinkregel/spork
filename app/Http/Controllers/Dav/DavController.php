<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dav;

use App\Services\Dav\DavServerFactory;
use App\Services\Dav\Support\CapturingSapi;
use Illuminate\Http\Request;
use Sabre\HTTP\Request as SabreRequest;
use Symfony\Component\HttpFoundation\Response;

class DavController
{
    public function __construct(private readonly DavServerFactory $factory)
    {
    }

    public function __invoke(Request $request): Response
    {
        $server = $this->factory->make('/dav/');
        $server->sapi = new CapturingSapi;
        $server->httpRequest = $this->buildSabreRequest($request);

        $server->start();

        $sabreResponse = $server->httpResponse;

        $body = $sabreResponse->getBody();
        if (is_resource($body)) {
            $payload = stream_get_contents($body) ?: '';
        } elseif (is_callable($body)) {
            ob_start();
            $body();
            $payload = (string) ob_get_clean();
        } else {
            $payload = (string) $body;
        }

        $headers = [];
        foreach ($sabreResponse->getHeaders() as $name => $values) {
            $headers[$name] = is_array($values) ? implode(', ', $values) : (string) $values;
        }

        return new Response(
            $payload,
            $sabreResponse->getStatus() ?: 200,
            $headers,
        );
    }

    private function buildSabreRequest(Request $request): SabreRequest
    {
        $headers = [];
        foreach ($request->headers->all() as $key => $values) {
            $headers[$key] = is_array($values) ? implode(', ', $values) : (string) $values;
        }

        $sabreRequest = new SabreRequest(
            $request->getMethod(),
            $request->getRequestUri(),
            $headers,
        );

        $rawBody = $request->getContent(true);
        if (is_resource($rawBody)) {
            $sabreRequest->setBody($rawBody);
        } else {
            $sabreRequest->setBody($request->getContent());
        }

        $sabreRequest->setAbsoluteUrl($request->getSchemeAndHttpHost().$request->getRequestUri());
        $sabreRequest->setRawServerData($request->server->all());

        return $sabreRequest;
    }
}
