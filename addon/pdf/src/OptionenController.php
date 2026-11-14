<?php
/**
 * PDF-Addon fuer den Liga Manager Online
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace Lmo\Addon\Pdf;

use Lmo\Web\Controller\Controller;
use Lmo\Web\Kontext;
use Lmo\Web\Meldung;
use Lmo\Web\Seite;
use Lmo\Web\Vorlagen;

/**
 * Die Optionsseite des Addons in der Administration (todo=pdfoptions, nur der Hauptadmin): die
 * Einstellungen in config/pdf/cfg.txt.
 */
final class OptionenController implements Controller
{
    public const TODO = 'pdfoptions';

    /**
     * Die Einstellungen: Schluessel in cfg.txt => erlaubte Werte (der erste ist die Vorgabe) oder die
     * Art: text (eine Zeile), datei (ein Dateiname ohne Ordner) oder farbe (#rrggbb).
     */
    public const EINSTELLUNGEN = [
        'lmo_pdf_homepage' => 'text',
        'lmo_show_gameday' => ['1', '0'],
        'lmo_show_table' => ['1', '0'],
        'lmo_show_nextgameday' => ['1', '0'],
        'lmo_pdf_format' => ['0', '1'],
        'lmo_pdf_anzeige' => ['1', '0'],
        'lmo_pdf_serie' => ['1', '0'],
        'lmo_pdf_teamnamen' => ['0', '1', '2'],
        'lmo_pdf_teamfett' => ['0', '1'],
        'lmo_show_pdfimg' => ['1', '0'],
        'lmo_pdf_bild' => 'datei',
        'lmo_show_rectangle' => ['1', '0'],
        'lmo_pdf_balkenfarbe' => 'farbe',
        'lmo_pdf_schriftfarbe' => 'farbe',
        'lmo_pdf_matchday_icon' => ['0', '1'],
        'lmo_pdf_table_icon' => ['1', '0'],
        'lmo_pdf_nmatchday_icon' => ['0', '1'],
        'lmo_pdf_schedule_icon' => ['0', '1'],
        'lmo_pdf_teamplanicon' => ['0', '1'],
    ];

    /** Die Farben: Schluessel => [Vorgabe, Praefix der drei Werte im alten Addon] */
    private const FARBEN = [
        'lmo_pdf_balkenfarbe' => [Gestaltung::BALKENFARBE, 'RectangleColor'],
        'lmo_pdf_schriftfarbe' => [Gestaltung::SCHRIFTFARBE, 'TextColor'],
    ];

    /**
     * @param string $ordner Ordner des Addons (addon/pdf)
     * @param string $konfigurationsordner Ordner der Konfiguration (PATH_TO_CONFIGDIR) mit pdf/cfg.txt
     */
    public function __construct(private string $ordner, private string $konfigurationsordner)
    {
    }

    public function zustaendig(Kontext $kontext): bool
    {
        return $kontext->anfrage->text('todo') == self::TODO && $kontext->sitzung->zahl('lmouserok') == 2;
    }

    public function zeige(Kontext $kontext): Seite
    {
        $a = $kontext->anfrage;
        $text = $kontext->text;
        $konfiguration = $kontext->konfiguration;
        $meldung = '';
        if ($a->text('save') == '1') {
            $neu = [];
            foreach (self::EINSTELLUNGEN as $schluessel => $art) {
                // ungueltige Werte (auch ein fehlendes Feld) behalten den bisherigen
                $wert = $a->gesetzt('x' . $schluessel) ? self::gueltig($art, $a->text('x' . $schluessel)) : null;
                if ($wert !== null) {
                    $neu[$schluessel] = $wert;
                }
            }
            $konfiguration = $konfiguration->mit(['pdf' => array_replace(self::werte($konfiguration->addon('pdf')), $neu)]);
            if (!is_dir($this->konfigurationsordner . '/pdf')) {
                @mkdir($this->konfigurationsordner . '/pdf', 0777, true);
            }
            $meldung = $konfiguration->speichere($this->konfigurationsordner)
                ? Meldung::html($kontext->pfade->bilder, $text[138] ?? '')
                : Meldung::html($kontext->pfade->bilder, $text[283] ?? '', true);
        }

        return new Seite($meldung . Vorlagen::zeige($this->ordner . '/vorlagen', 'admin_optionen.html.twig', [
            'text' => $text,
            't' => $kontext->texteVon('pdf'),
            'adresse' => $kontext->adresse,
            'todo' => self::TODO,
            'k' => self::werte($konfiguration->addon('pdf')),
        ]));
    }

    /**
     * Die Einstellungen mit Vorgaben fuer fehlende oder ungueltige; andere Werte in cfg.txt bleiben.
     * Fehlt eine Farbe, gilt die des alten Addons (drei Werte 0.0 bis 1.0), sonst die Vorgabe.
     *
     * @param array<string, string> $cfg
     * @return array<string, string>
     */
    public static function werte(array $cfg): array
    {
        $werte = [];
        foreach (self::EINSTELLUNGEN as $schluessel => $art) {
            $wert = self::gueltig($art, $cfg[$schluessel] ?? null);
            $werte[$schluessel] = match (true) {
                $wert !== null => $wert,
                $art === 'farbe' => Gestaltung::farbe($cfg, $schluessel, self::FARBEN[$schluessel][1], self::FARBEN[$schluessel][0]),
                $art === 'datei' => 'logo.png',
                $art === 'text' => '',
                default => $art[0],
            };
        }

        return $werte + $cfg;
    }

    /**
     * Ein Wert, wie er gespeichert wird; null, wenn er nicht gueltig ist.
     *
     * @param list<string>|string $art
     */
    private static function gueltig(array|string $art, ?string $wert): ?string
    {
        if ($wert === null) {
            return null;
        }
        $wert = trim(str_replace(["\r", "\n"], '', $wert));

        return match (true) {
            is_array($art) => in_array($wert, $art, true) ? $wert : null,
            $art === 'farbe' => preg_match('/^#[0-9a-fA-F]{6}$/', $wert) === 1 ? strtolower($wert) : null,
            // nur ein Name im Ordner img/pdf, kein Weg hinaus
            $art === 'datei' => $wert !== '' && basename($wert) === $wert && !str_starts_with($wert, '.') ? $wert : null,
            default => $wert,
        };
    }
}
