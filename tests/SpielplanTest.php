<?php

declare(strict_types=1);

namespace Lmo\PdfAddon\Tests;

use Lmo\PdfAddon\Tests\Support\Instanz;
use PHPUnit\Framework\TestCase;
use Smalot\PdfParser\Parser;

/**
 * Der neue Spielplan (pdf-spielplan.php) gegen den alten (Snapshots in tests/__snapshots__/alt):
 * dieselben Spieltage mit denselben Partien und Ergebnissen, in derselben Reihenfolge.
 */
class SpielplanTest extends TestCase
{
    private static ?Instanz $instanz = null;

    /** @return array<string, array{string, string}> Snapshot des alten Addons => Abfrage */
    public static function faelle(): array
    {
        return [
            'Spielplan_basic' => ['Spielplan_basic', 'file=golden/basic.l98'],
            'Spielplan_Wertungen' => ['Spielplan_Wertungen', 'file=golden/wertung.l98'],
        ];
    }

    private static function instanz(): Instanz
    {
        return self::$instanz ??= Instanz::neu()->mitNeuemAddon();
    }

    private static function text(string $abfrage): string
    {
        $pdf = self::instanz()->aufruf('addon/pdf/pdf-spielplan.php', $abfrage);
        self::assertStringStartsWith('%PDF-', $pdf);

        return str_replace("\r\n", "\n", (new Parser())->parseContent($pdf)->getText());
    }

    /** @dataProvider faelle */
    public function testInhaltWieImAltenAddon(string $snapshot, string $abfrage): void
    {
        $alt = (string) file_get_contents(__DIR__ . '/__snapshots__/alt/' . $snapshot . '.txt');
        $neu = self::text($abfrage);

        // Spieltage in ihrer Reihenfolge (das alte setzt die Titel einer Seite vor ihre Partien)
        preg_match_all('/^(\d+)\. Spieltag$/m', $alt, $a);
        preg_match_all('/^(\d+)\. Spieltag$/m', $neu, $n);
        self::assertNotEmpty($a[1]);
        self::assertSame($a[1], $n[1], 'Spieltage');

        // Partien: Heim, Gast, Ergebnis (das alte zeigte "_ : _" fuer ungespielte, das neue nichts)
        preg_match_all('/(Mannschaft \d+)(Mannschaft \d+)\s(\S+ : \S+)/', $alt, $a, PREG_SET_ORDER);
        preg_match_all('/(Mannschaft \d+) -(Mannschaft \d+)(?:\t(\S+ : \S+))?$/m', $neu, $n, PREG_SET_ORDER);
        $partien = static fn (array $treffer): array => array_map(static fn (array $t): string => $t[1] . ' - ' . $t[2] . ' ' . (($t[3] ?? '') === '_ : _' ? '' : ($t[3] ?? '')), $treffer);
        self::assertNotEmpty($a);
        self::assertSame($partien($a), $partien($n), 'Partien');
    }

    public function testKopfUndDateiname(): void
    {
        self::assertMatchesRegularExpression('/\d\d\.\d\d\.\d{4} - \d\d:\d\d Uhr/', self::text('file=golden/basic.l98'), 'Zeit der Erzeugung');
        self::assertStringContainsString('basic - Spielplan', self::text('file=golden/basic.l98'));
    }

    public function testVierSpieltageJeSeite(): void
    {
        $pdf = self::instanz()->aufruf('addon/pdf/pdf-spielplan.php', 'file=golden/basic.l98');
        $seiten = (new Parser())->parseContent($pdf)->getPages();

        self::assertCount(4, $seiten, '14 Spieltage');
        self::assertSame(['1', '2', '3', '4'], self::spieltage($seiten[0]->getText()));
        self::assertSame(['13', '14'], self::spieltage($seiten[3]->getText()));
    }

    public function testUnbekannteLiga(): void
    {
        $antwort = self::instanz()->aufruf('addon/pdf/pdf-spielplan.php', 'file=fehlt.l98');

        self::assertStringContainsString('class="error"', $antwort);
        self::assertStringContainsString('fehlt.l98', $antwort);
    }

    /** @return list<string> */
    private static function spieltage(string $text): array
    {
        preg_match_all('/^(\d+)\. Spieltag$/m', str_replace("\r\n", "\n", $text), $t);

        return $t[1];
    }
}
