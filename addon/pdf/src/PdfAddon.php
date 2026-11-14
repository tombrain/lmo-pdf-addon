<?php
/**
 * PDF-Addon fuer den Liga Manager Online
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace Lmo\Addon\Pdf;

use Lmo\Addon\Menuepunkt;
use Lmo\Addon\MitAdminMenue;
use Lmo\Addon\MitAdminSeiten;
use Lmo\Addon\MitRahmenteilen;
use Lmo\Addon\MitZusatzUnterSeite;
use Lmo\Web\Kontext;
use Lmo\Web\Vorlagen;

/**
 * Das PDF-Addon: Links auf die PDFs unter den Ergebnissen (Spieltag, Spielplan) und unter dem
 * Spielplan einer Mannschaft (Teamplan), dazu die Optionsseite in der Administration. Die PDFs
 * selbst liefern die Einstiege pdf-*.php im Ordner des Addons.
 */
final class PdfAddon implements MitRahmenteilen, MitZusatzUnterSeite, MitAdminMenue, MitAdminSeiten
{
    /**
     * @param string $ordner Ordner des Addons (addon/pdf)
     * @param string $konfigurationsordner Ordner der Konfiguration (PATH_TO_CONFIGDIR) mit pdf/cfg.txt
     */
    public function __construct(private string $ordner, private string $konfigurationsordner)
    {
    }

    public function name(): string
    {
        return 'pdf';
    }

    /** Unter den Ergebnissen einer Liga (bei den Links zur Druckansicht): Spieltag und Spielplan als PDF */
    public function rahmenteile(Kontext $kontext): array
    {
        $geladen = $kontext->liga;
        if ($geladen === null || $geladen->liga->istPokal() || $kontext->action !== 'results') {
            return [];
        }
        $datei = rawurlencode($geladen->datei);
        $st = $kontext->anfrage->zahl('st');
        $t = $kontext->texteVon('pdf');

        return ['Savehtml' => $this->links([
            ['adresse' => $this->adresse($kontext, 'pdf-tabelle.php') . '?file=' . $datei . ($st > 0 ? '&amp;st=' . $st : ''), 'name' => $t[2] ?? '', 'titel' => $t[3] ?? ''],
            ['adresse' => $this->adresse($kontext, 'pdf-spielplan.php') . '?file=' . $datei, 'name' => $t[0] ?? '', 'titel' => $t[1] ?? ''],
        ])];
    }

    /** Unter dem Spielplan einer gewaehlten Mannschaft: ihr Teamplan als PDF */
    public function zusatzUnterSeite(Kontext $kontext): string
    {
        $geladen = $kontext->liga;
        $mannschaft = $kontext->anfrage->zahl('selteam');
        if ($geladen === null || $geladen->liga->istPokal() || $kontext->action !== 'program' || $mannschaft < 1) {
            return '';
        }
        $t = $kontext->texteVon('pdf');

        return $this->links([
            ['adresse' => $this->adresse($kontext, 'pdf-teamplan.php') . '?file=' . rawurlencode($geladen->datei) . '&amp;selteam=' . $mannschaft, 'name' => $t[100] ?? '', 'titel' => $t[101] ?? ''],
        ]);
    }

    /** Der Punkt "PDF-Design" im Hauptmenue der Administration */
    public function adminMenue(Kontext $kontext): array
    {
        $t = $kontext->texteVon('pdf');

        return [new Menuepunkt(OptionenController::TODO, $t[200] ?? '', $t[201] ?? '', $kontext->anfrage->text('todo') == OptionenController::TODO)];
    }

    public function ligaMenue(Kontext $kontext): array
    {
        return [];
    }

    public function startseite(Kontext $kontext): array
    {
        return [];
    }

    public function adminController(): array
    {
        return [new OptionenController($this->ordner, $this->konfigurationsordner)];
    }

    /** @param list<array{adresse: string, name: string, titel: string}> $links */
    private function links(array $links): string
    {
        return Vorlagen::zeige($this->ordner . '/vorlagen', 'links.html.twig', ['links' => $links]);
    }

    private function adresse(Kontext $kontext, string $skript): string
    {
        return $kontext->pfade->adresse . '/addon/pdf/' . $skript;
    }
}
