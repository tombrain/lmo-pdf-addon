<?php

declare(strict_types=1);

namespace Lmo\PdfAddon\Tests;

use Lmo\PdfAddon\Tests\Support\Instanz;
use PHPUnit\Framework\TestCase;
use Smalot\PdfParser\Parser;

/**
 * Der neue Teamplan (pdf-teamplan.php) gegen den alten (Snapshots in tests/__snapshots__/alt, mit
 * Hin- und Rueckrunde getrennt): dieselben Spiele der Mannschaft mit Ergebnis, in derselben
 * Reihenfolge; dazu die Einstellungen des Addons.
 */
class TeamplanTest extends TestCase
{
    private static ?Instanz $instanz = null;

    /** @return array<string, array{string, string}> Snapshot des alten Addons => Abfrage */
    public static function faelle(): array
    {
        return [
            'Mannschaft 1' => ['Teamplan_basic_Mannschaft_1', 'file=golden/basic.l98&selteam=1'],
            'Mannschaft 6' => ['Teamplan_basic_Mannschaft_6', 'file=golden/basic.l98&selteam=6'],
        ];
    }

    /** @param array<string, string> $cfg */
    private static function pdf(string $abfrage, array $cfg = []): string
    {
        self::$instanz ??= Instanz::neu()->mitNeuemAddon();
        self::$instanz->mitKonfiguration($cfg);
        $pdf = self::$instanz->aufruf('addon/pdf/pdf-teamplan.php', $abfrage);
        self::assertStringStartsWith('%PDF-', $pdf);

        return $pdf;
    }

    /**
     * Der Text je Seite.
     *
     * @param array<string, string> $cfg
     * @return list<string>
     */
    private static function seiten(string $abfrage, array $cfg = []): array
    {
        return array_map(
            static fn ($seite): string => str_replace("\r\n", "\n", $seite->getText()),
            array_values((new Parser())->parseContent(self::pdf($abfrage, $cfg))->getPages()),
        );
    }

    /** @return list<string> die Partien im Text einer Seite: "Heim - Gast Ergebnis" */
    private static function partien(string $text): array
    {
        $partien = [];
        foreach (explode("\n", $text) as $zeile) {
            // ohne den Titel ("Hinrunde - Mannschaft 1") und die Zeit der Erzeugung
            if (preg_match('/runde - |Spielplan - |Uhr\s*$/u', $zeile) !== 1
                && preg_match('/^(\S[^\t]*?) - (\S[^\t]*?)(?:\t(\S+ : \S+))?$/', $zeile, $t) === 1) {
                $partien[] = $t[1] . ' - ' . $t[2] . ' ' . ($t[3] ?? '');
            }
        }

        return $partien;
    }

    /** @dataProvider faelle */
    public function testInhaltWieImAltenAddon(string $snapshot, string $abfrage): void
    {
        $alt = (string) file_get_contents(__DIR__ . '/__snapshots__/alt/' . $snapshot . '.txt');
        $seiten = self::seiten($abfrage);

        // je eine Seite fuer Hin- und Rueckrunde, wie im alten
        preg_match_all('/(Mannschaft \d+)-  (Mannschaft \d+) (\S+ : \S+)/', $alt, $a, PREG_SET_ORDER);
        $alt = array_map(static fn (array $t): string => $t[1] . ' - ' . $t[2] . ' ' . ($t[3] === '_ : _' ? '' : $t[3]), $a);
        self::assertCount(14, $alt);
        self::assertCount(2, $seiten);
        self::assertSame($alt, array_merge(self::partien($seiten[0]), self::partien($seiten[1])), 'Partien');
        self::assertStringContainsString('Hinrunde - Mannschaft', $seiten[0]);
        self::assertStringContainsString('Rückrunde - Mannschaft', $seiten[1]);
        self::assertMatchesRegularExpression('/\d\d\.\d\d\.\d{4} - \d\d:\d\d Uhr/', $seiten[0], 'Zeit der Erzeugung');
    }

    public function testOhneTrennungEineSeite(): void
    {
        $seiten = self::seiten('file=golden/basic.l98&selteam=1', ['lmo_pdf_serie' => '0']);

        self::assertCount(1, $seiten);
        self::assertStringContainsString('Spielplan - Mannschaft 1', $seiten[0]);
        self::assertCount(14, self::partien($seiten[0]));
    }

    public function testKurznamen(): void
    {
        $seiten = self::seiten('file=golden/basic.l98&selteam=1', ['lmo_pdf_teamnamen' => '1']);
        $partien = self::partien($seiten[0]);

        self::assertNotEmpty($partien);
        self::assertStringNotContainsString('Mannschaft', implode("\n", $partien), 'Kurznamen statt der langen');
        self::assertStringContainsString('Hinrunde - Mannschaft 1', $seiten[0], 'die Mannschaft im Titel mit ihrem Namen');
    }

    public function testAusrichtung(): void
    {
        $quer = (new Parser())->parseContent(self::pdf('file=golden/basic.l98&selteam=1'))->getPages()[0]->getDetails();
        $hoch = (new Parser())->parseContent(self::pdf('file=golden/basic.l98&selteam=1', ['lmo_pdf_format' => '1']))->getPages()[0]->getDetails();

        self::assertGreaterThan($quer['MediaBox'][3], $quer['MediaBox'][2], 'Vorgabe quer (wie bisher)');
        self::assertGreaterThan($hoch['MediaBox'][2], $hoch['MediaBox'][3], 'lmo_pdf_format=1 hoch');
    }

    public function testOhneMannschaftDieBitte(): void
    {
        $seiten = self::seiten('file=golden/basic.l98');

        self::assertCount(1, $seiten);
        self::assertStringContainsString('Bitte wählen Sie jetzt eine Mannschaft aus.', $seiten[0]);
    }

    public function testUnbekannteLiga(): void
    {
        self::$instanz ??= Instanz::neu()->mitNeuemAddon();
        $antwort = self::$instanz->aufruf('addon/pdf/pdf-teamplan.php', 'file=fehlt.l98&selteam=1');

        self::assertStringContainsString('class="error"', $antwort);
        self::assertStringContainsString('fehlt.l98', $antwort);
    }
}
