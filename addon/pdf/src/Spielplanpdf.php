<?php
/**
 * PDF-Addon fuer den Liga Manager Online
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace Lmo\Addon\Pdf;

/**
 * Der Spielplan als PDF (pdf-spielplan.php?file=<liga>): Liga laden, Werte (Spielplan), Vorlage
 * spielplan.html.twig, PDF (Dompdf).
 */
final class Spielplanpdf extends PdfSeite
{
    /**
     * Die Antwort.
     *
     * @param string $jetzt Zeit der Erzeugung, wie sie im Kopf steht
     * @return array{kopf: list<string>, inhalt: string}
     */
    public function antwort(string $datei, string $jetzt): array
    {
        $liga = $this->liga($datei);
        if ($liga === null) {
            return $this->nichtGefunden($datei);
        }
        $werte = Spielplan::werte($liga);

        // Dateiname wie bisher: "Ligaspielplan_<Liga>"
        return $this->pdf('spielplan.html.twig', $werte + ['jetzt' => $jetzt], $this->t(107) . '_' . $werte['name'], $liga, ['spielplan']);
    }
}
