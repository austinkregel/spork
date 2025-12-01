<?php

declare(strict_types=1);

namespace App\Contracts\Services\Documents;

use Smalot\PdfParser\Document;

interface PdfReaderServiceContract
{
    public function parseContent(string $content): Document;
}

