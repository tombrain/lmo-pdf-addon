<?php

declare(strict_types=1);

namespace Lmo\PdfAddon\Tests;

use Lmo\PdfAddon\Tests\Support\Instanz;
use PHPUnit\Framework\TestCase;
use Smalot\PdfParser\Parser;

/**
 * Das neue Spieltagsblatt (pdf-tabelle.php) gegen das alte (Snapshots in tests/__snapshots__/alt):
 * dieselben Partien mit Ergebnis und dieselbe Tabelle (Platz, Mannschaft, Punkte), in derselben
 * Reihenfolge. Die Gestaltung ist neu (Dompdf), verglichen wird nur der Inhalt.
 */
class SpieltagsblattTest extends TestCase
{
    private static ?Instanz $instanz = null;

    /** @return array<string, array{string, string}> Snapshot des alten Addons => Abfrage */
    public static function faelle(): array
    {
        return [
            'Tabelle_basic_5_Spieltag' => ['Tabelle_basic_5_Spieltag', 'file=golden/basic.l98&st=5'],
            'Tabelle_basic_letzter_Spieltag' => ['Tabelle_basic_letzter_Spieltag', 'file=golden/basic.l98&st=14'],
            'Tabelle_Wertungen' => ['Tabelle_Wertungen', 'file=golden/wertung.l98&st=3'],
            'Tabelle_Minuspunkte' => ['Tabelle_Minuspunkte', 'file=golden/minus2_direct.l98&st=4'],
        ];
    }

    private static function text(string $abfrage): string
    {
        self::$instanz ??= Instanz::neu()->mitNeuemAddon();
        $pdf = self::$instanz->aufruf('addon/pdf/pdf-tabelle.php', $abfrage);
        self::assertStringStartsWith('%PDF-', $pdf);

        return str_replace("\r\n", "\n", (new Parser())->parseContent($pdf)->getText());
    }

    /** @dataProvider faelle */
    public function testInhaltWieImAltenAddon(string $snapshot, string $abfrage): void
    {
        $alt = (string) file_get_contents(__DIR__ . '/__snapshots__/alt/' . $snapshot . '.txt');
        $neu = self::text($abfrage);

        // Partien: Heim, Gast, Ergebnis (das alte zeigte "_ : _" fuer ungespielte, das neue nichts)
        preg_match_all('/(Mannschaft \d+)(Mannschaft \d+)\t(\S+ : \S+)/', $alt, $a, PREG_SET_ORDER);
        preg_match_all('/(Mannschaft \d+) -(Mannschaft \d+)(?:\t(\S+ : \S+))?$/m', $neu, $n, PREG_SET_ORDER);
        $partien = static fn (array $treffer): array => array_map(static fn (array $t): string => $t[1] . ' - ' . $t[2] . ' ' . (($t[3] ?? '') === '_ : _' ? '' : ($t[3] ?? '')), $treffer);
        self::assertNotEmpty($a);
        self::assertSame($partien($a), $partien($n), 'Partien');

        // Tabelle: Platz, Mannschaft, Punkte (letzte Zahl der Zeile; mit Minuspunkten zeigt das neue
        // wie die Tabelle des LMO "12:0", das alte nur die Pluspunkte)
        preg_match_all('/^(\d+)(Mannschaft \d+)\t.*?(\d+)$/m', $alt, $a, PREG_SET_ORDER);
        preg_match_all('/^(\d+)(Mannschaft \d+)\t.*?(\d+)(?::\d+)?$/m', $neu, $n, PREG_SET_ORDER);
        $tabelle = static fn (array $treffer): array => array_map(static fn (array $t): string => $t[1] . '. ' . $t[2] . ' ' . $t[3], $treffer);
        self::assertNotEmpty($a);
        self::assertSame($tabelle($a), $tabelle($n), 'Tabelle');
    }

    public function testKopfUndSpalten(): void
    {
        $text = self::text('file=golden/basic.l98&st=5');

        self::assertMatchesRegularExpression('/\d\d\.\d\d\.\d{4} - \d\d:\d\d Uhr/', $text, 'Zeit der Erzeugung');
        self::assertStringContainsString('Tabelle - 5. Spieltag', $text);
        self::assertStringContainsString('Mannschaft', $text, 'Spaltenkopf "Mannschaft" (das alte: "Wechsel in den Adminbereich")');
        self::assertStringNotContainsString('Wechsel in den Adminbereich', $text);
        self::assertStringContainsString('6. Spieltag', $text, 'der folgende Spieltag');
    }

    public function testSaisonende(): void
    {
        self::assertStringContainsString('Saisonende', self::text('file=golden/basic.l98&st=14'));
    }

    public function testUnbekannteLiga(): void
    {
        self::$instanz ??= Instanz::neu()->mitNeuemAddon();
        $antwort = self::$instanz->aufruf('addon/pdf/pdf-tabelle.php', 'file=fehlt.l98');

        self::assertStringContainsString('class="error"', $antwort);
        self::assertStringContainsString('fehlt.l98', $antwort);
    }
}
