<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Documents;

use App\Services\Documents\MenuCardParser;
use Mockery;
use Tests\TestCase;

class MenuCardParserTest extends TestCase
{
    public function test_get_all_identifiers_parses_expected_codes(): void
    {
        $pdfText = <<<'TXT'
This recall affects products from Store A
Package #1A4X Some product description
Another line of text
TXT;

        $parserService = Mockery::mock(\App\Contracts\Services\Documents\PdfParserServiceContract::class);
        $parserService->shouldReceive('getPdfTextFromFile')
            ->once()
            ->with('dummy.pdf')
            ->andReturn($pdfText);

        $parser = new MenuCardParser($parserService);

        $identifiers = $parser->getAllIdentifiers('dummy.pdf');

        $this->assertContains('1A4X', $identifiers);
    }
}
