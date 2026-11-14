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

    /** Die Einstellungen: Schluessel in cfg.txt => erlaubte Werte (der erste ist die Vorgabe); null: freier Text */
    public const EINSTELLUNGEN = [
        'lmo_pdf_homepage' => null,
        'lmo_show_gameday' => ['1', '0'],
        'lmo_show_table' => ['1', '0'],
        'lmo_show_nextgameday' => ['1', '0'],
        'lmo_pdf_format' => ['0', '1'],
        'lmo_pdf_anzeige' => ['1', '0'],
        'lmo_pdf_serie' => ['1', '0'],
        'lmo_pdf_teamnamen' => ['0', '1', '2'],
        'lmo_pdf_teamfett' => ['0', '1'],
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
            foreach (self::EINSTELLUNGEN as $schluessel => $erlaubt) {
                $wert = trim($a->text('x' . $schluessel));
                // unbekannte Werte (auch ein fehlendes Feld) behalten den bisherigen
                if ($a->gesetzt('x' . $schluessel) && ($erlaubt === null || in_array($wert, $erlaubt, true))) {
                    $neu[$schluessel] = $erlaubt === null ? str_replace(["\r", "\n"], '', $wert) : $wert;
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
     *
     * @param array<string, string> $cfg
     * @return array<string, string>
     */
    public static function werte(array $cfg): array
    {
        $werte = [];
        foreach (self::EINSTELLUNGEN as $schluessel => $erlaubt) {
            $wert = $cfg[$schluessel] ?? null;
            $werte[$schluessel] = $erlaubt === null ? (string) $wert : (in_array($wert, $erlaubt, true) ? (string) $wert : $erlaubt[0]);
        }

        return $werte + $cfg;
    }
}
