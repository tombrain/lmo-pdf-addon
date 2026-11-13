<?php

declare(strict_types=1);

namespace Lmo\PdfAddon\Tests;

use Dompdf\Dompdf;
use PHPUnit\Framework\TestCase;
use Smalot\PdfParser\Parser;

/**
 * Grundlage der Tests: Dompdf (Laufzeit des Addons) erzeugt aus HTML ein PDF, aus dem der Parser
 * den Text wieder liest. Der Golden Master vergleicht die PDFs ueber diesen Text.
 */
class PdfErzeugungTest extends TestCase
{
    public function testHtmlWirdPdfMitLesbaremText(): void
    {
        $dompdf = new Dompdf();
        $dompdf->loadHtml('<html><body><h1>Tabelle</h1><table><tr><td>1.</td><td>Bayern München</td><td>79</td></tr></table></body></html>');
        $dompdf->setPaper('A4');
        $dompdf->render();
        $pdf = (string) $dompdf->output();

        self::assertStringStartsWith('%PDF-', $pdf);
        $text = (new Parser())->parseContent($pdf)->getText();
        foreach (['Tabelle', 'Bayern München', '79'] as $teil) {
            self::assertStringContainsString($teil, $text);
        }
    }
}
