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
use Lmo\Liga\Spiel;
use Lmo\Liga\Tabelle;

/**
 * Das Spieltagsblatt (bis 1.x pdf-tabelle.php): der gewaehlte Spieltag, die Tabelle bis dahin und der
 * folgende Spieltag (am letzten: Saisonende). Liefert die Werte fuer die Vorlage spieltag.html.twig.
 */
final class Spieltagsblatt
{
    /**
     * Die Werte.
     *
     * @param int $st der Spieltag; 0 oder ausserhalb: der aktuelle der Liga
     * @return array{name: string, st: int, spiele: list<array<string, string>>, tabelle: list<array<string, int|string>>, folge: array{st: int, spiele: list<array<string, string>>}|null}
     */
    public static function werte(Liga $liga, int $st): array
    {
        $e = $liga->einstellungen();
        if ($st <= 0 || $st > $e->spieltage()) {
            $st = max(1, $e->aktuellerSpieltag());
        }
        $tabelle = Tabelle::fuerAnsicht($liga, Tabelle::GESAMT, $st);
        $reihenfolge = $e->mitHandicap()
            ? $tabelle->reihenfolgeNachHandicap((string) ($liga->spieltage[$e->aktuellerSpieltag()]->werte['HS'] ?? '0'))
            : $tabelle->reihenfolge;
        $zeilen = [];
        foreach ($reihenfolge as $i => $nr) {
            $z = $tabelle->zeilen[$nr] ?? null;
            if ($z === null) {
                continue;
            }
            $zeilen[] = [
                'platz' => $i + 1,
                'name' => $liga->mannschaftsname($nr),
                'spiele' => $z->spiele,
                'siege' => $z->siege,
                'unentschieden' => $z->unentschieden,
                'niederlagen' => $z->niederlagen,
                'tore' => $z->tore,
                'gegentore' => $z->gegentore,
                'differenz' => $z->tordifferenz(),
                'punkte' => $e->mitMinuspunkten() ? $z->punkte . ':' . $z->minuspunkte : (string) $z->punkte,
            ];
        }

        return [
            'name' => $e->name(),
            'st' => $st,
            'spiele' => self::spiele($liga, $st),
            'tabelle' => $zeilen,
            'folge' => $st < $e->spieltage() ? ['st' => $st + 1, 'spiele' => self::spiele($liga, $st + 1)] : null,
        ];
    }

    /**
     * Die Spiele eines Spieltags mit beiden Mannschaften; nach Anstoss, wenn die Liga so sortiert
     * (enableGameSort).
     *
     * @return list<array<string, string>>
     */
    public static function spiele(Liga $liga, int $st): array
    {
        $spiele = array_values(array_filter(iterator_to_array($liga->spiele($st), false), static fn (Spiel $s): bool => $s->heim > 0 && $s->gast > 0));
        if (($liga->optionen['enableGameSort'] ?? '') == '1') {
            usort($spiele, static fn (Spiel $a, Spiel $b): int => ($a->anstoss ?? 0) <=> ($b->anstoss ?? 0));
        }

        return array_map(static fn (Spiel $s): array => self::zeile($s), $spiele);
    }

    /**
     * Ein Spiel als Zeile: Datum, Uhrzeit, beide Mannschaften und Ergebnis.
     *
     * @param string $namen welcher Name der Mannschaften: 'anzeigename', 'kurzname' oder 'mittelname'
     * @return array<string, string>
     */
    public static function zeile(Spiel $s, string $namen = 'anzeigename'): array
    {
        return [
            'datum' => $s->anstoss !== null ? date('d.m.Y', $s->anstoss) : '',
            'zeit' => $s->anstoss !== null ? date('H:i', $s->anstoss) : '',
            'heim' => $s->heimMannschaft()->$namen(),
            'gast' => $s->gastMannschaft()->$namen(),
            // eine Wertung am gruenen Tisch mit Stern an der Seite, gegen die gewertet wurde (wie bisher)
            'ergebnis' => $s->istGespielt() ? $s->toreHeimText() . ($s->wertung === 1 ? '*' : '') . ' : ' . $s->toreGastText() . ($s->wertung === 2 ? '*' : '') : '',
        ];
    }
}
