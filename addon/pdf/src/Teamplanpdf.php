<?php
/**
 * PDF-Addon fuer den Liga Manager Online
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace Lmo\Addon\Pdf;

/**
 * Der Teamplan als PDF (pdf-teamplan.php?file=<liga>&selteam=<mannschaft>): Liga laden, Werte
 * (Teamplan), Vorlage teamplan.html.twig, PDF (Dompdf). Ohne gueltige Mannschaft steht im PDF die
 * Bitte, eine zu waehlen (wie bisher).
 */
final class Teamplanpdf extends PdfSeite
{
    /**
     * Die Antwort.
     *
     * @param string $jetzt Zeit der Erzeugung, wie sie im Kopf steht
     * @return array{kopf: list<string>, inhalt: string}
     */
    public function antwort(string $datei, int $mannschaft, string $jetzt): array
    {
        $liga = $this->liga($datei);
        if ($liga === null) {
            return $this->nichtGefunden($datei);
        }
        $werte = Teamplan::werte(
            $liga,
            $mannschaft,
            $this->wert('lmo_pdf_anzeige', '1') != '0',
            $this->wert('lmo_pdf_serie', '1') != '0',
            (int) $this->wert('lmo_pdf_teamnamen', '0'),
        );
        $ausrichtung = $this->wert('lmo_pdf_format', '0') == '1' ? 'portrait' : 'landscape';
        if ($werte === null) {
            return $this->pdf('teamplan.html.twig', ['name' => $liga->einstellungen()->name(), 'mannschaft' => '', 'abschnitte' => [], 'jetzt' => $jetzt], $this->t(104), $ausrichtung);
        }

        // Dateiname wie bisher: "Spielplan_<Mannschaft>"
        return $this->pdf('teamplan.html.twig', $werte + [
            'jetzt' => $jetzt,
            'fett' => $this->wert('lmo_pdf_teamfett', '0') == '1',
        ], $this->t(104) . '_' . $werte['mannschaft'], $ausrichtung);
    }
}
