<?php
/**
 * PDF-Addon fuer den Liga Manager Online
 *
 * Neu geschrieben fuer den LMO mit Twig (https://github.com/tombrain/lmo_php8), nach dem PDF-Addon
 * von henshingly (https://github.com/henshingly/lmo-pdf-addon), Teamplan von HT.
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace Lmo\Addon\Pdf;

use Lmo\Liga\Rohdaten\Liga;
use Lmo\Liga\Spiel;

/**
 * Der Teamplan (bis 1.x pdf-teamplan.php): alle Spiele einer Mannschaft, auf Wunsch getrennt nach
 * Hin- und Rueckrunde. Liefert die Werte fuer die Vorlage teamplan.html.twig.
 */
final class Teamplan
{
    /** Welcher Name der Mannschaften, nach lmo_pdf_teamnamen (0 lang, 1 kurz, 2 mittel) */
    private const NAMEN = ['anzeigename', 'kurzname', 'mittelname'];

    /**
     * Die Werte; null, wenn es die Mannschaft nicht gibt.
     *
     * @param bool $nachSpieltag nach Spieltag und Anstoss sortieren (lmo_pdf_anzeige=1), sonst nur nach Anstoss
     * @param bool $getrennt Hin- und Rueckrunde je fuer sich (lmo_pdf_serie=1); ihre Grenze ist die Mitte der Spieltage
     * @param int $namen welcher Name der Mannschaften (lmo_pdf_teamnamen: 0 lang, 1 kurz, 2 mittel)
     * @return array{name: string, mannschaft: string, abschnitte: list<array{runde: string, spiele: list<array<string, string|bool>>}>}|null
     *         runde: 'hin', 'rueck' oder 'alle'; je Spiel zusaetzlich 'heimEigen' (die Mannschaft spielt zu Hause)
     */
    public static function werte(Liga $liga, int $mannschaft, bool $nachSpieltag, bool $getrennt, int $namen): ?array
    {
        $e = $liga->einstellungen();
        if ($mannschaft < 1 || $mannschaft > $e->mannschaften()) {
            return null;
        }
        $spiele = [];
        for ($st = 1; $st <= $e->spieltage(); $st++) {
            foreach ($liga->spiele($st) as $s) {
                if ($s->hatMannschaften() && ($s->heim === $mannschaft || $s->gast === $mannschaft)) {
                    $spiele[] = $s;
                }
            }
        }
        if (!$nachSpieltag) {
            // stabil: gleicher (oder kein) Anstoss bleibt in der Reihenfolge der Spieltage
            usort($spiele, static fn (Spiel $a, Spiel $b): int => ($a->anstoss ?? 0) <=> ($b->anstoss ?? 0));
        }
        $methode = self::NAMEN[$namen] ?? self::NAMEN[0];
        $abschnitte = [];
        foreach ($spiele as $s) {
            $runde = !$getrennt ? 'alle' : ($s->spieltag > intdiv($e->spieltage(), 2) ? 'rueck' : 'hin');
            $abschnitte[$runde][] = Spieltagsblatt::zeile($s, $methode) + ['heimEigen' => $s->heim === $mannschaft];
        }

        return [
            'name' => $e->name(),
            'mannschaft' => $liga->mannschaftsname($mannschaft),
            // die Hinrunde vor der Rueckrunde, auch wenn nach Anstoss sortiert ein Spiel frueher liegt
            'abschnitte' => array_values(array_filter(array_map(
                static fn (string $runde): array => ['runde' => $runde, 'spiele' => $abschnitte[$runde] ?? []],
                ['hin', 'rueck', 'alle'],
            ), static fn (array $a): bool => $a['spiele'] !== [])),
        ];
    }
}
