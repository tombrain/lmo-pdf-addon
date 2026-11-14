<?php
/**
 * PDF-Addon fuer den Liga Manager Online
 *
 * Neu geschrieben fuer den LMO mit Twig (https://github.com/tombrain/lmo_php8), nach dem PDF-Addon
 * von henshingly (https://github.com/henshingly/lmo-pdf-addon), PDF Spielplan von Tim Schumacher.
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace Lmo\Addon\Pdf;

use Lmo\Liga\Rohdaten\Liga;

/**
 * Der Spielplan (bis 1.x pdf-spielplan.php): alle Spieltage mit ihren Spielen. Liefert die Werte fuer
 * die Vorlage spielplan.html.twig.
 */
final class Spielplan
{
    /**
     * Die Werte.
     *
     * @return array{name: string, spieltage: list<array{st: int, spiele: list<array<string, string>>}>}
     */
    public static function werte(Liga $liga): array
    {
        $e = $liga->einstellungen();
        $spieltage = [];
        for ($st = 1; $st <= $e->spieltage(); $st++) {
            $spieltage[] = ['st' => $st, 'spiele' => Spieltagsblatt::spiele($liga, $st)];
        }

        return ['name' => $e->name(), 'spieltage' => $spieltage];
    }
}
