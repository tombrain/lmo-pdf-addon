# PDF-Addon für den Liga Manager Online

Spieltag, Tabelle, Spielplan und Teamplan als PDF-Dokument, für den
[LMO mit PHP 8 und Twig](https://github.com/tombrain/lmo_php8).

Das Addon ist neu geschrieben: Es erzeugt die PDFs mit [Dompdf](https://github.com/dompdf/dompdf) aus
Twig-Vorlagen und bindet sich über die Erweiterungspunkte des LMO ein. Am LMO selbst muss nichts
geändert werden. Inhalt, Einstellungen und Aussehen folgen dem bisherigen PDF-Addon
(siehe [Herkunft](#herkunft)).

*English summary: [below](#english).*

## Was es kann

| PDF | Einstieg | Link im LMO |
|---|---|---|
| **Spieltagsblatt**: Spieltag, Tabelle bis dahin, folgender Spieltag | `addon/pdf/pdf-tabelle.php?file=<liga>&st=<spieltag>` | unter den Ergebnissen: „Spieltag als PDF-Dokument“ |
| **Spielplan**: alle Spieltage, vier je Seite | `addon/pdf/pdf-spielplan.php?file=<liga>` | unter den Ergebnissen: „Ligaspielplan als PDF-Dokument“ |
| **Teamplan**: alle Spiele einer Mannschaft, Hin- und Rückrunde | `addon/pdf/pdf-teamplan.php?file=<liga>&selteam=<nr>` | unter dem Spielplan einer gewählten Mannschaft: „Spielplan als PDF-Dokument“ |

Die Links öffnen das PDF in einem neuen Fenster. Für Pokale gibt es keine PDFs.

In der Administration steht für den Hauptadmin der Menüpunkt **PDF-Design** mit allen Einstellungen.

## Voraussetzungen

- LMO aus [tombrain/lmo_php8](https://github.com/tombrain/lmo_php8), Branch `twig` (mit den
  Erweiterungspunkten für Addons nach neuer Art)
- PHP 8.1 bis 8.5 mit den Erweiterungen `gd`, `mbstring` und `dom` (braucht Dompdf)

## Installation

1. Das ZIP `lmo-pdf-addon-<version>.zip` von den
   [Releases](https://github.com/tombrain/lmo-pdf-addon/releases) laden.
2. In den Ordner des LMO entpacken (dorthin, wo `lmo.php` liegt). Es entsteht der Ordner
   `addon/pdf` mit allem, was das Addon braucht, auch Dompdf in `addon/pdf/vendor`.
3. Fertig: Die Links erscheinen unter den Ergebnissen und dem Spielplan, der Menüpunkt
   „PDF-Design“ in der Administration.

Die Einstellungen stehen in `config/pdf/cfg.txt`. Fehlt die Datei, gelten die Vorgaben aus
`addon/pdf/config-default/cfg.txt`; das erste Speichern auf der Seite „PDF-Design“ legt sie an.
Ein Update des Addons ersetzt nur `addon/pdf` und lässt die Einstellungen in Ruhe.

### Logo und Wappen

- **Logo**: eine Bilddatei (PNG, JPG oder GIF) in `img/pdf/`, Vorgabe `logo.png`. Es steht oben auf
  jeder Seite. Gibt es die Datei nicht, bleibt der Platz leer.
- **Wappen**: die kleinen Wappen des LMO aus `img/teams/small/`, gesucht wie im LMO selbst.

Die Bilder kommen eingebettet ins PDF. Dompdf lädt nichts aus dem Netz und liest keine Dateien
außerhalb des Addons.

## Einstellungen

Alle Werte lassen sich auf der Seite „PDF-Design“ ändern.

| Schlüssel | Vorgabe | Bedeutung |
|---|---|---|
| `lmo_pdf_homepage` | leer | Text am Rand jeder Seite, z. B. die Adresse der Homepage |
| `lmo_show_pdfimg` | `1` | Logo zeigen |
| `lmo_pdf_bild` | `logo.png` | Datei des Logos in `img/pdf/` (nur ein Dateiname) |
| `lmo_show_rectangle` | `1` | Balken links und rechts, die Homepage im linken; ohne steht sie unten |
| `lmo_pdf_balkenfarbe` | `#0000ff` | Farbe der Balken |
| `lmo_pdf_schriftfarbe` | `#ffffff` | Farbe der Homepage im Balken |
| `lmo_show_gameday` | `1` | Spieltagsblatt: den Spieltag zeigen |
| `lmo_show_table` | `1` | Spieltagsblatt: die Tabelle zeigen |
| `lmo_show_nextgameday` | `1` | Spieltagsblatt: den folgenden Spieltag zeigen (am letzten: „Saisonende“) |
| `lmo_pdf_matchday_icon` | `0` | Wappen im Spieltag |
| `lmo_pdf_table_icon` | `1` | Wappen in der Tabelle |
| `lmo_pdf_nmatchday_icon` | `0` | Wappen im folgenden Spieltag |
| `lmo_pdf_schedule_icon` | `0` | Wappen im Spielplan |
| `lmo_pdf_teamplanicon` | `0` | Wappen im Teamplan |
| `lmo_pdf_format` | `0` | Teamplan: `0` quer, `1` hoch |
| `lmo_pdf_anzeige` | `1` | Teamplan: `1` nach Spieltag, `0` nach Datum sortiert |
| `lmo_pdf_serie` | `1` | Teamplan: `1` Hin- und Rückrunde je auf einer Seite, `0` zusammen |
| `lmo_pdf_teamnamen` | `0` | Teamplan: `0` langer, `2` mittlerer, `1` kurzer Name |
| `lmo_pdf_teamfett` | `0` | Teamplan: die gewählte Mannschaft fett |

Ist in der Liga die Sortierung nach Datum eingestellt, stehen die Spiele eines Spieltags nach
Anstoß sortiert.

## Umstieg vom alten PDF-Addon

Das alte Addon (R&OS-PDF-Klasse, Version 3.x) läuft mit PHP 8 und dem aktuellen LMO nicht mehr; der
LMO bindet es auch nicht mehr ein.

1. Den alten Ordner `addon/pdf` ganz löschen, dann das neue Addon entpacken.
2. Löschen kann man auch, was das alte Addon sonst mitbrachte: `addon/classlib/classes/pdf/`
   (die PDF-Klasse) und `lang/pdf/` (die Texte liegen jetzt in `addon/pdf/lang/`).
3. **Behalten**: `config/pdf/cfg.txt` und `img/pdf/`. Die Einstellungen gelten weiter; die Farben
   des alten Addons (`RectangleColor*`, `TextColor*`, Werte 0.0 bis 1.0) werden übernommen, bis sie
   auf der Seite „PDF-Design“ neu gespeichert werden.
4. Die Code-Änderungen, die das alte Addon in `lmo-adminmain.php` und anderen Dateien des LMO
   verlangte, entfallen.

Nicht mehr gebraucht werden die Einstellungen zu Schriften, Schriftgrößen, Spaltenbreiten,
Rasterlinien, Icongrößen und Tabellendesign; das Aussehen kommt jetzt aus den Vorlagen.

Was sich gegenüber dem alten Addon im Inhalt ändert:

- Ungespielte Spiele und fehlende Anstoßzeiten stehen leer statt als `_ : _` oder `__.__.____`.
- Mit Minuspunkten zeigt die Tabelle die Punkte wie der LMO als „12:0“.
- Die Spalte der Mannschaften heißt „Mannschaft“ (das alte Addon zeigte dort einen falschen Text).

## Anpassen

Das Aussehen steht in den Twig-Vorlagen in `addon/pdf/vorlagen/`:

| Vorlage | Inhalt |
|---|---|
| `_stil.html.twig` | Stil aller PDFs (Ränder, Schrift, Tabellen, Balken) |
| `_rahmen.html.twig` | was auf jeder Seite steht: Zeit, Logo, Balken, Homepage |
| `_makros.html.twig` | die Tabelle der Spiele eines Spieltags |
| `spieltag.html.twig`, `spielplan.html.twig`, `teamplan.html.twig` | die drei PDFs |
| `links.html.twig`, `admin_optionen.html.twig` | die Links im LMO und die Seite „PDF-Design“ |

Wer sie ändert, sollte das vor einem Update sichern; das ZIP bringt die mitgelieferten Vorlagen mit.

## Entwicklung

Der Code des Addons liegt in `addon/pdf/src` (Namensraum `Lmo\Addon\Pdf`, PHP 8.1 mit
`strict_types`). Die Tests laufen gegen einen echten LMO: Composer holt `lmo/lmo_php8` (Branch
`twig`), die Tests bauen daraus eine Installation in einem temporären Ordner.

```sh
composer install                                   # LMO, PHPUnit, PDF-Parser
composer install --no-dev --working-dir=addon/pdf  # Dompdf nach addon/pdf/vendor
vendor/bin/phpunit
```

- `tests/altes-addon/` enthält das alte Addon als Referenz. `AltesAddonTest` hält seinen Inhalt
  als Text fest (`tests/__snapshots__/alt/`); die Tests der neuen PDFs vergleichen damit Partien,
  Ergebnisse und Tabelle.
- Die CI (`.github/workflows/tests.yml`) testet mit PHP 8.1, 8.3, 8.4 und 8.5.
- Ein Tag `v*` baut das ZIP und legt es als Release an (`.github/workflows/release.yml`).

## Herkunft

Neu geschrieben 2026 für den LMO mit Twig, nach

- dem PDF-Addon von henshingly (Dietmar Kersting),
  [henshingly/lmo-pdf-addon](https://github.com/henshingly/lmo-pdf-addon), Version 3.2.0,
- dem PDF-Spielplan von Tim Schumacher (timme) und Torsten Hofmann,
- dem Teamplan von plastic (HT).

Die R&OS-PDF-Klasse von Wayne Munro und der Teamvergleich gehören nicht mehr zum Addon.

## Lizenz

GNU General Public License, Version 3 oder später (`GPL-3.0-or-later`), siehe [LICENSE](LICENSE).
Dompdf steht unter der LGPL 2.1.

## Changelog

### 4.0.0 – unveröffentlicht

- Neu geschrieben mit Dompdf und Twig für den LMO mit PHP 8 (Branch `twig`).
- Einbindung über die Erweiterungspunkte des LMO; keine Änderungen am LMO mehr nötig.
- Spieltagsblatt, Spielplan und Teamplan mit den bisherigen Einstellungen.
- Farben von Balken und Homepage neu als Farbwähler (`lmo_pdf_balkenfarbe`,
  `lmo_pdf_schriftfarbe`); die alten Werte werden übernommen.
- Weggefallen: R&OS-PDF-Klasse, Teamvergleich, Einstellungen zu Schriften und Maßen,
  Farbhilfe-PDFs.

### Bis 3.2.0 (henshingly)

Die Geschichte des alten Addons (2.2 von 2012 bis 3.2.0 von 2023) steht in
[henshingly/lmo-pdf-addon](https://github.com/henshingly/lmo-pdf-addon).

## English

PDF documents of matchday, table, fixtures and team schedule for the
[Liga Manager Online with PHP 8 and Twig](https://github.com/tombrain/lmo_php8), rewritten with
Dompdf and Twig. Download the ZIP from the releases, unpack it into the LMO folder (it creates
`addon/pdf`) and you are done: links appear below the results and the fixtures, and the admin
menu gets the page “PDF-Design” for all settings. Settings of the old add-on in
`config/pdf/cfg.txt` keep working; delete the old `addon/pdf` folder first. Requires PHP 8.1 to
8.5 with `gd`, `mbstring` and `dom`. License: GPL-3.0-or-later.
