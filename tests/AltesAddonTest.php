<?php

declare(strict_types=1);

namespace Lmo\PdfAddon\Tests;

use Lmo\PdfAddon\Tests\Support\Instanz;
use PHPUnit\Framework\TestCase;
use Smalot\PdfParser\Parser;

/**
 * Golden Master des alten Addons (tests/altes-addon, Stand henshingly, mit der PHP-8-tauglichen
 * PDF-Klasse 0.12.67 aus dem LMO): der Text der PDFs fuer verschiedene Ligen und Aufrufe. Das neue
 * Addon gestaltet die PDFs neu (Dompdf); diese Snapshots halten fest, welcher Inhalt darin stand.
 * Aufnehmen: GOLDEN_MODE=record (sonst wird verglichen; fehlende Snapshots legt record-missing an).
 */
class AltesAddonTest extends TestCase
{
    private static ?Instanz $instanz = null;

    /** @return array<string, array{string, string}> Name => [Skript, Abfrage] */
    public static function aufrufe(): array
    {
        return [
            'Tabelle, basic, 5. Spieltag' => ['pdf-tabelle.php', 'file=golden/basic.l98&st=5'],
            'Tabelle, basic, letzter Spieltag' => ['pdf-tabelle.php', 'file=golden/basic.l98&st=14'],
            'Tabelle, Wertungen' => ['pdf-tabelle.php', 'file=golden/wertung.l98&st=3'],
            'Tabelle, Minuspunkte' => ['pdf-tabelle.php', 'file=golden/minus2_direct.l98&st=4'],
            'Tabelle, Bundesliga' => ['pdf-tabelle.php', 'file=golden/1l_2024-25.l98&st=1'],
            'Spielplan, basic' => ['pdf-spielplan.php', 'file=golden/basic.l98'],
            'Spielplan, Wertungen' => ['pdf-spielplan.php', 'file=golden/wertung.l98'],
            'Teamplan, basic, Mannschaft 1' => ['pdf-teamplan.php', 'file=golden/basic.l98&selteam=1'],
            'Teamplan, basic, Mannschaft 6' => ['pdf-teamplan.php', 'file=golden/basic.l98&selteam=6'],
        ];
    }

    /** @dataProvider aufrufe */
    public function testText(string $skript, string $abfrage): void
    {
        self::$instanz ??= Instanz::neu()->mitAltemAddon();
        $pdf = self::$instanz->aufruf('addon/pdf/' . $skript, $abfrage);
        self::assertStringStartsWith('%PDF-', $pdf, $skript);

        $text = (new Parser())->parseContent($pdf)->getText();
        // Zeit der Erzeugung
        $text = (string) preg_replace('/\d\d\.\d\d\.\d{4} - \d\d:\d\d Uhr/', '{JETZT}', $text);
        $this->vergleiche('alt/' . preg_replace('/[^A-Za-z0-9]+/', '_', (string) $this->dataName()), $text);
    }

    private function vergleiche(string $name, string $text): void
    {
        $datei = __DIR__ . '/__snapshots__/' . $name . '.txt';
        $modus = (string) getenv('GOLDEN_MODE');
        $text = str_replace("\r\n", "\n", $text);
        if ($modus === 'record' || ($modus === 'record-missing' && !is_file($datei))) {
            if (!is_dir(dirname($datei))) {
                mkdir(dirname($datei), 0777, true);
            }
            file_put_contents($datei, $text);
            $this->addToAssertionCount(1);

            return;
        }
        self::assertFileExists($datei, 'Snapshot fehlt; aufnehmen mit GOLDEN_MODE=record-missing');
        self::assertSame((string) file_get_contents($datei), $text, $name);
    }
}
