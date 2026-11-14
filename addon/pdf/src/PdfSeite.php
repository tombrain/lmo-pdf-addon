<?php
/**
 * PDF-Addon fuer den Liga Manager Online
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace Lmo\Addon\Pdf;

use Lmo\Liga\Rohdaten\Liga;
use Lmo\Web\LigaLader;
use Lmo\Web\Meldung;
use Lmo\Web\Vorlagen;

/**
 * Gemeinsames der PDF-Seiten: Liga laden (unbekannt oder Pokal: Meldung als HTML), Vorlage zeigen,
 * PDF als Antwort, Werte aus Konfiguration und Sprachtexten.
 */
abstract class PdfSeite
{
    /**
     * @param array<string, mixed> $cfg die Konfiguration des Addons (config/pdf/cfg.txt)
     * @param array<int|string, mixed> $text die Sprachtexte (die des Addons unter 'pdf')
     * @param string $ordner Ordner des Addons (Vorlagen, Bilder)
     * @param string $bilder Adresse der Bilder des LMO (fuer die Meldung)
     */
    public function __construct(protected LigaLader $lader, protected array $cfg, protected array $text, protected string $ordner, protected string $bilder)
    {
    }

    /** Die Liga einer Datei; null, wenn es sie nicht gibt oder sie ein Pokal ist */
    protected function liga(string $datei): ?Liga
    {
        $geladen = $datei !== '' ? $this->lader->lade($datei) : null;

        return $geladen === null || $geladen->liga->istPokal() ? null : $geladen->liga;
    }

    /**
     * Die Meldung "Liga nicht gefunden" als HTML.
     *
     * @return array{kopf: list<string>, inhalt: string}
     */
    protected function nichtGefunden(string $datei): array
    {
        return [
            'kopf' => ['Content-Type: text/html; charset=utf-8'],
            'inhalt' => Meldung::html($this->bilder, '<b><u>' . htmlspecialchars($this->t(13)) . ':</u></b> ' . htmlspecialchars($datei), true),
        ];
    }

    /**
     * Eine Vorlage als PDF.
     *
     * @param array<string, mixed> $werte
     * @param string $name Dateiname ohne Endung (Zeichen ausser Buchstaben, Ziffern, . und - fallen weg)
     * @return array{kopf: list<string>, inhalt: string}
     */
    protected function pdf(string $vorlage, array $werte, string $name, string $ausrichtung = 'portrait'): array
    {
        $html = Vorlagen::zeige($this->ordner . '/vorlagen', $vorlage, $werte + [
            'text' => $this->text,
            't' => is_array($this->text['pdf'] ?? null) ? $this->text['pdf'] : [],
            'homepage' => $this->wert('lmo_pdf_homepage'),
        ]);
        $name = (string) preg_replace('/[^\w.-]+/u', '', $name) . '.pdf';

        return [
            'kopf' => ['Content-Type: application/pdf', 'Content-Disposition: inline; filename="' . $name . '"'],
            'inhalt' => Pdf::aus($html, $this->ordner, $ausrichtung),
        ];
    }

    protected function wert(string $schluessel, string $vorgabe = ''): string
    {
        $wert = $this->cfg[$schluessel] ?? $vorgabe;

        return is_scalar($wert) ? (string) $wert : $vorgabe;
    }

    /** Ein Sprachtext des Addons */
    protected function t(int $nr): string
    {
        $pdf = is_array($this->text['pdf'] ?? null) ? $this->text['pdf'] : [];
        $wert = $pdf[$nr] ?? '';

        return is_scalar($wert) ? (string) $wert : '';
    }
}
