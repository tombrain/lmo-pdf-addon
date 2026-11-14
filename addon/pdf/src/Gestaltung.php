<?php
/**
 * PDF-Addon fuer den Liga Manager Online
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace Lmo\Addon\Pdf;

use Lmo\Liga\Rohdaten\Liga;
use Lmo\Web\Bilder;

/**
 * Die Gestaltung der PDFs aus der Konfiguration: Logo (img/pdf/<Datei>), die Balken links und rechts
 * mit der Homepage und die Wappen der Mannschaften. Bilder kommen als data:-Adresse in das HTML,
 * damit Dompdf weder das Netz noch Dateien ausserhalb des Addons braucht.
 */
final class Gestaltung
{
    /** Vorgaben der Farben wie im alten Addon: blauer Balken, weisse Schrift */
    public const BALKENFARBE = '#0000ff';
    public const SCHRIFTFARBE = '#ffffff';

    /** Die Schalter fuer Wappen: Teil des PDFs => Schluessel in cfg.txt */
    public const WAPPEN = [
        'spieltag' => 'lmo_pdf_matchday_icon',
        'tabelle' => 'lmo_pdf_table_icon',
        'folge' => 'lmo_pdf_nmatchday_icon',
        'spielplan' => 'lmo_pdf_schedule_icon',
        'teamplan' => 'lmo_pdf_teamplanicon',
    ];

    private const ARTEN = ['gif' => 'image/gif', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];

    /**
     * @param array<string, mixed> $cfg die Konfiguration des Addons
     * @param string $bilderordner Pfad zum Bilderordner des LMO (PATH_TO_IMGDIR)
     */
    public function __construct(private array $cfg, private string $bilderordner)
    {
    }

    /**
     * Die Werte fuer die Vorlagen (_stil, _rahmen): logo (data:-Adresse oder ''), balken (null ohne)
     * und wappen (Name der Mannschaft in allen Formen => data:-Adresse, nur wenn ein Teil sie zeigt).
     *
     * @param list<string> $teile die Teile des PDFs, deren Wappen-Schalter gelten (Schluessel von WAPPEN)
     * @return array{logo: string, balken: array{farbe: string, schrift: string}|null, wappen: array<string, string>, mitWappen: array<string, bool>}
     */
    public function werte(?Liga $liga, array $teile): array
    {
        $mitWappen = [];
        foreach ($teile as $teil) {
            $mitWappen[$teil] = $this->wert(self::WAPPEN[$teil] ?? '', $teil === 'tabelle' ? '1' : '0') == '1';
        }

        return [
            'logo' => $this->wert('lmo_show_pdfimg', '1') == '1' ? $this->logo() : '',
            'balken' => $this->wert('lmo_show_rectangle', '1') == '1'
                ? ['farbe' => self::farbe($this->cfg, 'lmo_pdf_balkenfarbe', 'RectangleColor', self::BALKENFARBE), 'schrift' => self::farbe($this->cfg, 'lmo_pdf_schriftfarbe', 'TextColor', self::SCHRIFTFARBE)]
                : null,
            'wappen' => $liga !== null && in_array(true, $mitWappen, true) ? $this->wappen($liga) : [],
            'mitWappen' => $mitWappen,
        ];
    }

    /**
     * Eine Farbe als #rrggbb: der neue Schluessel, sonst die drei Werte 0.0 bis 1.0 des alten Addons
     * (<alt>Red, <alt>Green, <alt>Blue), sonst die Vorgabe.
     *
     * @param array<string, mixed> $cfg
     */
    public static function farbe(array $cfg, string $schluessel, string $alt, string $vorgabe): string
    {
        $wert = $cfg[$schluessel] ?? null;
        if (is_string($wert) && preg_match('/^#[0-9a-fA-F]{6}$/', $wert) === 1) {
            return strtolower($wert);
        }
        $teile = [];
        foreach (['Red', 'Green', 'Blue'] as $farbe) {
            $anteil = $cfg[$alt . $farbe] ?? null;
            if (!is_numeric($anteil)) {
                return $vorgabe;
            }
            $teile[] = (int) round(max(0.0, min(1.0, (float) $anteil)) * 255);
        }

        return vsprintf('#%02x%02x%02x', $teile);
    }

    /** Das Logo (img/pdf/<lmo_pdf_bild>) als data:-Adresse; '' ohne */
    private function logo(): string
    {
        $name = basename($this->wert('lmo_pdf_bild', 'logo.png'));

        return $name !== '' ? self::dataAdresse($this->bilderordner . '/pdf/' . $name) : '';
    }

    /**
     * Die Wappen (img/teams/small, gesucht wie im LMO) unter allen Namen der Mannschaften.
     *
     * @return array<string, string>
     */
    private function wappen(Liga $liga): array
    {
        $bilder = new Bilder($this->bilderordner, '');
        $wappen = [];
        for ($nr = 1; $nr <= $liga->einstellungen()->mannschaften(); $nr++) {
            $m = $liga->mannschaft($nr);
            $datei = $bilder->datei($m->anzeigename(), 'teams');
            $adresse = $datei !== null ? self::dataAdresse($datei) : '';
            if ($adresse !== '') {
                foreach ([$m->anzeigename(), $m->kurzname(), $m->mittelname()] as $name) {
                    $wappen[$name] ??= $adresse;
                }
            }
        }

        return $wappen;
    }

    private static function dataAdresse(string $datei): string
    {
        $art = self::ARTEN[strtolower(pathinfo($datei, PATHINFO_EXTENSION))] ?? null;
        $inhalt = $art !== null && is_file($datei) ? file_get_contents($datei) : false;

        return $inhalt !== false && $inhalt !== '' ? 'data:' . $art . ';base64,' . base64_encode($inhalt) : '';
    }

    private function wert(string $schluessel, string $vorgabe): string
    {
        $wert = $this->cfg[$schluessel] ?? $vorgabe;

        return is_scalar($wert) ? (string) $wert : $vorgabe;
    }
}
