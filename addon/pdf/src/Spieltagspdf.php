<?php
/**
 * PDF-Addon fuer den Liga Manager Online
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace Lmo\Addon\Pdf;

/**
 * Das Spieltagsblatt als PDF (pdf-tabelle.php?file=<liga>&st=<spieltag>): Liga laden, Werte
 * (Spieltagsblatt), Vorlage spieltag.html.twig, PDF (Dompdf).
 */
final class Spieltagspdf extends PdfSeite
{
    /**
     * Die Antwort.
     *
     * @param string $jetzt Zeit der Erzeugung, wie sie im Kopf steht
     * @return array{kopf: list<string>, inhalt: string}
     */
    public function antwort(string $datei, int $st, string $jetzt): array
    {
        $liga = $this->liga($datei);
        if ($liga === null) {
            return $this->nichtGefunden($datei);
        }
        $werte = Spieltagsblatt::werte($liga, $st);

        return $this->pdf('spieltag.html.twig', $werte + [
            'jetzt' => $jetzt,
            'zeigen' => [
                'spieltag' => $this->wert('lmo_show_gameday', '1') != '0',
                'tabelle' => $this->wert('lmo_show_table', '1') != '0',
                'folge' => $this->wert('lmo_show_nextgameday', '1') != '0',
            ],
        ], $werte['name'] . '-' . $werte['st'], $liga, ['spieltag', 'tabelle', 'folge']);
    }
}
