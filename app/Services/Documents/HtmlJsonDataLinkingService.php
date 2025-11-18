<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Contracts\Services\Documents\HtmlJsonDataLinkingServiceContract;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

class HtmlJsonDataLinkingService implements HtmlJsonDataLinkingServiceContract
{
    public function __construct(
        protected Client $client,
    ) {}

    public function fetchDataLink(string $url): array
    {
        $page = $this->client->get($url);

        $content = $page->getBody()->getContents();

        $dom = new \DOMDocument;
        $dom->loadHTML($content, LIBXML_NOERROR);
        libxml_use_internal_errors(true);
        $linkedDataSets = [];
        foreach ($dom->getElementsByTagName('script') as $script) {
            if ($script->getAttribute('type') == 'application/ld+json') {
                $json_txt = preg_replace('@/\*.*?\*/@', '', $script->textContent);
                $json_txt = preg_replace("/\r|\n/", ' ', trim($json_txt));
                $json = json_decode($json_txt);

                $linkedDataSets[] = $json;
            }
        }

        return $linkedDataSets;
    }
}
