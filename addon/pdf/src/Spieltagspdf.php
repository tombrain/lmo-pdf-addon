<?php
/**
 * PDF-Addon fuer den Liga Manager Online
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace Lmo\Addon\Pdf;

use Lmo\Web\LigaLader;
use Lmo\Web\Meldung;
use Lmo\Web\Vorlagen;

/**
 * Das Spieltagsblatt als PDF (pdf-tabelle.php?file=<liga>&st=<spieltag>): Liga laden, Werte
 * (Spieltagsblatt), Vorlage spieltag.html.twig, PDF (Dompdf).
 */
final class Spieltagspdf
{
    /**
     * @param array<string, mixed> $cfg die Konfiguration des Addons (config/pdf/cfg.txt)
     * @param array<int|string, mixed> $text die Sprachtexte (die des Addons unter 'pdf')
     * @param string $ordner Ordner des Addons (Vorlagen, Bilder)
     * @param string $bilder Adresse der Bilder des LMO (fuer die Meldung)
     */
    public function __construct(private LigaLader $lader, private array $cfg, private array $text, private string $ordner, private string $bilder)
    {
    }

    /**
     * Die Antwort.
     *
     * @param string $jetzt Zeit der Erzeugung, wie sie im Kopf steht
     * @return array{kopf: list<string>, inhalt: string}
     */
    public function antwort(string $datei, int $st, string $jetzt): array
    {
        $geladen = $datei !== '' ? $this->lader->lade($datei) : null;
        if ($geladen === null || $geladen->liga->istPokal()) {
            return [
                'kopf' => ['Content-Type: text/html; charset=utf-8'],
                'inhalt' => Meldung::html($this->bilder, '<b><u>' . htmlspecialchars($this->t(13)) . ':</u></b> ' . htmlspecialchars($datei), true),
            ];
        }
        $werte = Spieltagsblatt::werte($geladen->liga, $st);
        $html = Vorlagen::zeige($this->ordner . '/vorlagen', 'spieltag.html.twig', $werte + [
            'text' => $this->text,
            't' => is_array($this->text['pdf'] ?? null) ? $this->text['pdf'] : [],
            'jetzt' => $jetzt,
            'homepage' => $this->wert('lmo_pdf_homepage'),
            'zeigen' => [
                'spieltag' => $this->wert('lmo_show_gameday', '1') != '0',
                'tabelle' => $this->wert('lmo_show_table', '1') != '0',
                'folge' => $this->wert('lmo_show_nextgameday', '1') != '0',
            ],
        ]);
        $name = (string) preg_replace('/[^\w.-]+/u', '', $werte['name'] . '-' . $werte['st']) . '.pdf';

        return [
            'kopf' => ['Content-Type: application/pdf', 'Content-Disposition: inline; filename="' . $name . '"'],
            'inhalt' => Pdf::aus($html, $this->ordner),
        ];
    }

    private function wert(string $schluessel, string $vorgabe = ''): string
    {
        $wert = $this->cfg[$schluessel] ?? $vorgabe;

        return is_scalar($wert) ? (string) $wert : $vorgabe;
    }

    private function t(int $nr): string
    {
        $pdf = is_array($this->text['pdf'] ?? null) ? $this->text['pdf'] : [];
        $wert = $pdf[$nr] ?? '';

        return is_scalar($wert) ? (string) $wert : '';
    }
}
