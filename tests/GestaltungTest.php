<?php

declare(strict_types=1);

namespace Lmo\PdfAddon\Tests;

use Lmo\Addon\Pdf\Gestaltung;
use Lmo\Liga\Rohdaten\Liga;
use Lmo\PdfAddon\Tests\Support\Instanz;
use Lmo\Web\LigaLader;
use PHPUnit\Framework\TestCase;
use Smalot\PdfParser\Parser;

/**
 * Logo, Balken und Wappen aus der Konfiguration (Lmo\Addon\Pdf\Gestaltung).
 */
class GestaltungTest extends TestCase
{
    /** ein PNG mit 1 x 1 Bildpunkt */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFBQIAX8jx0gAAAABJRU5ErkJggg==';

    private string $bilder = '';

    protected function setUp(): void
    {
        $this->bilder = sys_get_temp_dir() . '/lmo-pdf-bilder-' . bin2hex(random_bytes(3));
        mkdir($this->bilder . '/pdf', 0777, true);
        mkdir($this->bilder . '/teams/small', 0777, true);
        file_put_contents($this->bilder . '/pdf/logo.png', base64_decode(self::PNG));
        file_put_contents($this->bilder . '/pdf/eigenes.png', base64_decode(self::PNG));
        file_put_contents($this->bilder . '/teams/small/Mannschaft 1.png', base64_decode(self::PNG));
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->bilder));
    }

    private static function liga(): Liga
    {
        $geladen = (new LigaLader(__DIR__ . '/fixtures/ligen/', 'd.m.Y'))->lade('basic.l98');
        self::assertNotNull($geladen);

        return $geladen->liga;
    }

    public function testVorgabenWieImAltenAddon(): void
    {
        $g = (new Gestaltung([], $this->bilder))->werte(self::liga(), ['spieltag', 'tabelle', 'folge']);

        self::assertStringStartsWith('data:image/png;base64,', $g['logo'], 'logo.png aus img/pdf');
        self::assertSame(['farbe' => '#0000ff', 'schrift' => '#ffffff'], $g['balken'], 'blauer Balken, weisse Schrift');
        self::assertSame(['spieltag' => false, 'tabelle' => true, 'folge' => false], $g['mitWappen'], 'Wappen nur in der Tabelle');
        self::assertArrayHasKey('Mannschaft 1', $g['wappen']);
        self::assertArrayNotHasKey('Mannschaft 2', $g['wappen'], 'ohne Bild kein Wappen');
    }

    public function testWappenUnterAllenNamen(): void
    {
        $g = (new Gestaltung([], $this->bilder))->werte(self::liga(), ['tabelle']);

        self::assertArrayHasKey('Manns', $g['wappen'], 'auch unter dem Kurznamen (lmo_pdf_teamnamen)');
    }

    public function testAbgeschaltet(): void
    {
        $g = (new Gestaltung(['lmo_show_pdfimg' => '0', 'lmo_show_rectangle' => '0', 'lmo_pdf_table_icon' => '0'], $this->bilder))->werte(self::liga(), ['tabelle']);

        self::assertSame('', $g['logo']);
        self::assertNull($g['balken']);
        self::assertSame([], $g['wappen'], 'kein Teil zeigt Wappen: keine Suche');
    }

    public function testLogoNurAusImgPdf(): void
    {
        self::assertNotSame('', (new Gestaltung(['lmo_pdf_bild' => 'eigenes.png'], $this->bilder))->werte(null, [])['logo']);
        self::assertSame('', (new Gestaltung(['lmo_pdf_bild' => 'fehlt.png'], $this->bilder))->werte(null, [])['logo']);
        file_put_contents($this->bilder . '/geheim.png', base64_decode(self::PNG));
        self::assertSame('', (new Gestaltung(['lmo_pdf_bild' => '../geheim.png'], $this->bilder))->werte(null, [])['logo'], 'kein Weg aus dem Ordner');
        file_put_contents($this->bilder . '/pdf/text.txt', 'kein Bild');
        self::assertSame('', (new Gestaltung(['lmo_pdf_bild' => 'text.txt'], $this->bilder))->werte(null, [])['logo'], 'nur Bilder');
    }

    public function testFarben(): void
    {
        self::assertSame('#ff8000', Gestaltung::farbe(['f' => '#FF8000'], 'f', 'Alt', '#000000'));
        self::assertSame('#ff8000', Gestaltung::farbe(['AltRed' => '1.0', 'AltGreen' => '0.5', 'AltBlue' => '0'], 'f', 'Alt', '#000000'), 'aus den Werten des alten Addons');
        self::assertSame('#ff0000', Gestaltung::farbe(['AltRed' => '7', 'AltGreen' => '-1', 'AltBlue' => '0'], 'f', 'Alt', '#000000'), 'begrenzt auf 0 bis 1');
        self::assertSame('#000000', Gestaltung::farbe(['f' => 'blau', 'AltRed' => '1.0'], 'f', 'Alt', '#000000'), 'unvollstaendig: die Vorgabe');
    }

    public function testImPdf(): void
    {
        $instanz = Instanz::neu()->mitNeuemAddon();
        @mkdir($instanz->lmo() . '/img/pdf', 0777, true);
        file_put_contents($instanz->lmo() . '/img/pdf/logo.png', base64_decode(self::PNG));

        $instanz->mitKonfiguration(['lmo_pdf_homepage' => 'www.example.org', 'lmo_pdf_balkenfarbe' => '#ff0000']);
        $mit = $instanz->aufruf('addon/pdf/pdf-tabelle.php', 'file=golden/basic.l98&st=5');
        self::assertStringContainsString('/Subtype /Image', $mit, 'das Logo');
        self::assertMatchesRegularExpression('/\b1(\.0+)? 0(\.0+)? 0(\.0+)? rg\b/', self::inhalt($mit), 'der Balken in Rot');
        self::assertStringContainsString('www.example.org', (new Parser())->parseContent($mit)->getText());

        $instanz->mitKonfiguration(['lmo_show_pdfimg' => '0', 'lmo_show_rectangle' => '0', 'lmo_pdf_balkenfarbe' => '#ff0000']);
        $ohne = $instanz->aufruf('addon/pdf/pdf-tabelle.php', 'file=golden/basic.l98&st=5');
        self::assertStringNotContainsString('/Subtype /Image', $ohne);
        self::assertDoesNotMatchRegularExpression('/\b1(\.0+)? 0(\.0+)? 0(\.0+)? rg\b/', self::inhalt($ohne));
    }

    /** Alle Streams eines PDFs, entpackt (Dompdf legt fixierte Teile in eigene Objekte) */
    private static function inhalt(string $pdf): string
    {
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $t);
        $inhalt = '';
        foreach ($t[1] as $stream) {
            $entpackt = @gzuncompress($stream);
            $inhalt .= ($entpackt !== false ? $entpackt : $stream) . "\n";
        }

        return $inhalt;
    }
}
