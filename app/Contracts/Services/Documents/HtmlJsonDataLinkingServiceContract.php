<?php

declare(strict_types=1);

namespace App\Contracts\Services\Documents;

interface HtmlJsonDataLinkingServiceContract
{
    /**
     * Fetch and extract JSON-LD structured data from HTML
     */
    public function fetchDataLink(string $url): array;
}
