<?php

declare(strict_types=1);

namespace Lmo\PdfAddon\Tests;

use Lmo\Addon\Pdf\OptionenController;
use Lmo\Addon\Pdf\PdfAddon;
use Lmo\Web\Anfrage;
use Lmo\Web\Darstellung;
use Lmo\Web\Konfiguration;
use Lmo\Web\Kontext;
use Lmo\Web\LigaLader;
use Lmo\Web\Pfade;
use Lmo\Web\Sitzung;
use PHPUnit\Framework\TestCase;

/**
 * Die Einbindung in den LMO (Lmo\Addon\Pdf\PdfAddon): Links unter den Ergebnissen und dem
 * Spielplan, der Punkt im Menue der Administration und die Optionsseite.
 */
class PdfAddonTest extends TestCase
{
    private string $config = '';

    protected function setUp(): void
    {
        $this->config = sys_get_temp_dir() . '/lmo-pdf-config-' . bin2hex(random_bytes(3));
        mkdir($this->config . '/pdf', 0777, true);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->config));
    }

    private function addon(): PdfAddon
    {
        return new PdfAddon(dirname(__DIR__) . '/addon/pdf', $this->config);
    }

    /**
     * @param array<string, mixed> $parameter
     * @param array<string, mixed> $konfiguration
     */
    private static function kontext(string $action, array $parameter = [], string $liga = 'basic.l98', int $rolle = 0, array $konfiguration = []): Kontext
    {
        $texte = [];
        foreach (file(dirname(__DIR__) . '/addon/pdf/lang/lang-Deutsch.txt', FILE_IGNORE_NEW_LINES) ?: [] as $zeile) {
            if (preg_match('/^(\d+)=(.*)$/', $zeile, $t) === 1) {
                $texte[(int) $t[1]] = $t[2];
            }
        }

        return new Kontext(
            new Anfrage($parameter + ['action' => $action]),
            $action,
            $liga !== '' ? (new LigaLader(__DIR__ . '/fixtures/ligen/', 'd.m.Y'))->lade($liga) : null,
            new Konfiguration($konfiguration),
            [114 => 'Speichern', 138 => 'Gespeichert', 188 => 'Speichern', 283 => 'Fehler'],
            $action === 'admin' ? '/lmo/lmoadmin.php' : '/lmo/lmo.php',
            new Pfade('/lmo', '', 'IMG', 'https://example.org/lmo'),
            new Darstellung(static fn (string $d, string $n, string $a): string => ''),
            weitereTexte: ['pdf' => $texte],
            sitzung: new Sitzung(['lmouserok' => $rolle])
        );
    }

    public function testLinksUnterDenErgebnissen(): void
    {
        $teile = $this->addon()->rahmenteile(self::kontext('results', ['st' => '5']));

        self::assertSame(['Savehtml'], array_keys($teile));
        self::assertStringContainsString('href="https://example.org/lmo/addon/pdf/pdf-tabelle.php?file=basic.l98&amp;st=5"', $teile['Savehtml']);
        self::assertStringContainsString('>Spieltag als PDF-Dokument</a>', $teile['Savehtml']);
        self::assertStringContainsString('href="https://example.org/lmo/addon/pdf/pdf-spielplan.php?file=basic.l98"', $teile['Savehtml']);
        self::assertStringContainsString('>Ligaspielplan als PDF-Dokument</a>', $teile['Savehtml']);
        self::assertStringContainsString('target="_blank"', $teile['Savehtml']);
    }

    public function testOhneSpieltagDerAktuelle(): void
    {
        $teile = $this->addon()->rahmenteile(self::kontext('results'));

        self::assertStringContainsString('pdf-tabelle.php?file=basic.l98"', $teile['Savehtml']);
    }

    public function testKeineLinksAufAnderenSeitenOderOhneLiga(): void
    {
        self::assertSame([], $this->addon()->rahmenteile(self::kontext('table')));
        self::assertSame([], $this->addon()->rahmenteile(self::kontext('results', [], '')));
    }

    public function testTeamplanUnterDemSpielplan(): void
    {
        $html = $this->addon()->zusatzUnterSeite(self::kontext('program', ['selteam' => '6']));

        self::assertStringContainsString('href="https://example.org/lmo/addon/pdf/pdf-teamplan.php?file=basic.l98&amp;selteam=6"', $html);
        self::assertStringContainsString('>Spielplan als PDF-Dokument</a>', $html);
        self::assertSame('', $this->addon()->zusatzUnterSeite(self::kontext('program')), 'ohne Mannschaft');
        self::assertSame('', $this->addon()->zusatzUnterSeite(self::kontext('results', ['selteam' => '6'])), 'nur unter dem Spielplan');
    }

    public function testMenuepunkt(): void
    {
        $punkte = $this->addon()->adminMenue(self::kontext('admin', ['todo' => 'pdfoptions'], '', 2));

        self::assertCount(1, $punkte);
        self::assertSame('pdfoptions', $punkte[0]->ziel);
        self::assertSame('PDF-Design', $punkte[0]->name);
        self::assertTrue($punkte[0]->aktiv);
    }

    public function testOptionenNurFuerDenHauptadmin(): void
    {
        $controller = $this->addon()->adminController()[0];

        self::assertTrue($controller->zustaendig(self::kontext('admin', ['todo' => 'pdfoptions'], '', 2)));
        self::assertFalse($controller->zustaendig(self::kontext('admin', ['todo' => 'pdfoptions'], '', 1)));
        self::assertFalse($controller->zustaendig(self::kontext('admin', ['todo' => 'options'], '', 2)));
    }

    public function testOptionenZeigen(): void
    {
        $html = $this->addon()->adminController()[0]->zeige(self::kontext('admin', ['todo' => 'pdfoptions'], '', 2, ['pdf' => ['lmo_pdf_homepage' => 'example.org', 'lmo_pdf_teamnamen' => '2']]))->html;

        self::assertStringContainsString('name="xlmo_pdf_homepage" size="30" maxlength="80" value="example.org"', $html);
        self::assertStringContainsString('name="xlmo_pdf_teamnamen" value="2" onClick="dolmoedit()" checked', $html);
        self::assertStringContainsString('name="xlmo_pdf_format" value="0" onClick="dolmoedit()" checked', $html, 'Vorgabe quer');
        self::assertStringContainsString('name="xlmo_show_table" value="1" onClick="dolmoedit()" checked', $html, 'Vorgabe an');
    }

    public function testOptionenSpeichern(): void
    {
        $seite = $this->addon()->adminController()[0]->zeige(self::kontext('admin', [
            'todo' => 'pdfoptions', 'save' => '1',
            'xlmo_pdf_homepage' => "  www.example.org\r\nboese=1 ",
            'xlmo_pdf_format' => '1',
            'xlmo_pdf_teamnamen' => '7',
            'xlmo_show_table' => '0',
        ], '', 2, ['deflang' => 'Deutsch', 'pdf' => ['lmo_pdf_teamnamen' => '1', 'eigenes' => 'x']]));

        self::assertStringContainsString('Gespeichert', $seite->html);
        $cfg = parse_ini_file($this->config . '/pdf/cfg.txt', false, INI_SCANNER_RAW);
        self::assertIsArray($cfg);
        self::assertSame('www.example.orgboese=1', $cfg['lmo_pdf_homepage'], 'eine Zeile');
        self::assertSame('1', $cfg['lmo_pdf_format']);
        self::assertSame('1', $cfg['lmo_pdf_teamnamen'], 'ungueltiger Wert: der bisherige bleibt');
        self::assertSame('0', $cfg['lmo_show_table']);
        self::assertSame('1', $cfg['lmo_show_gameday'], 'fehlend: Vorgabe');
        self::assertSame('x', $cfg['eigenes'], 'andere Werte bleiben');
        self::assertStringContainsString('name="xlmo_pdf_format" value="1" onClick="dolmoedit()" checked', $seite->html, 'gezeigt wird das Gespeicherte');
    }

    public function testImLmo(): void
    {
        $instanz = Support\Instanz::neu()->mitNeuemAddon();

        $ergebnisse = $instanz->aufruf('lmo.php', 'action=results&file=golden/basic.l98&st=3');
        self::assertStringContainsString('addon/pdf/pdf-tabelle.php?file=golden%2Fbasic.l98&amp;st=3', $ergebnisse);
        self::assertStringContainsString('addon/pdf/pdf-spielplan.php?file=golden%2Fbasic.l98', $ergebnisse);

        $spielplan = $instanz->aufruf('lmo.php', 'action=program&file=golden/basic.l98&selteam=2');
        self::assertStringContainsString('addon/pdf/pdf-teamplan.php?file=golden%2Fbasic.l98&amp;selteam=2', $spielplan);
    }

    public function testVorgabenWieConfigDefault(): void
    {
        $vorgabe = parse_ini_file(dirname(__DIR__) . '/addon/pdf/config-default/cfg.txt', false, INI_SCANNER_RAW);

        self::assertSame($vorgabe, OptionenController::werte([]));
    }
}
