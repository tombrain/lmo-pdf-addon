<?php
/**
 * PDF-Addon fuer den Liga Manager Online
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace Lmo\Addon\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Macht aus HTML (einer Twig-Vorlage) ein PDF, mit Dompdf. Nichts wird nachgeladen: Bilder kommen als
 * Pfad im Addon-Ordner oder als data:-Adresse.
 */
final class Pdf
{
    /**
     * @param string $ordner Ordner, aus dem Dompdf Dateien lesen darf (Bilder, Schriften)
     * @param string $ausrichtung portrait oder landscape
     */
    public static function aus(string $html, string $ordner, string $ausrichtung = 'portrait'): string
    {
        $optionen = new Options();
        $optionen->setIsRemoteEnabled(false);
        $optionen->setChroot($ordner);
        // DejaVu Sans kommt mit Dompdf und kann Umlaute
        $optionen->setDefaultFont('DejaVu Sans');
        $dompdf = new Dompdf($optionen);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', $ausrichtung === 'landscape' ? 'landscape' : 'portrait');
        $dompdf->render();

        return (string) $dompdf->output();
    }
}
