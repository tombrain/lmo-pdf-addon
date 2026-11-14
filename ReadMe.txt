PDF-Addon fuer den Liga Manager Online
======================================

Spieltag, Tabelle, Spielplan und Teamplan als PDF-Dokument, fuer den LMO mit
PHP 8 und Twig (https://github.com/tombrain/lmo_php8, Branch twig).
Neu geschrieben mit Dompdf und Twig nach dem PDF-Addon von henshingly.

Ausfuehrlich: README.md oder https://github.com/tombrain/lmo-pdf-addon


Voraussetzungen
---------------

- LMO aus tombrain/lmo_php8, Branch twig
- PHP 8.1 bis 8.5 mit den Erweiterungen gd, mbstring und dom


Installation
------------

1. Dieses ZIP in den Ordner des LMO entpacken (dorthin, wo lmo.php liegt).
   Es entsteht der Ordner addon/pdf, Dompdf liegt schon in addon/pdf/vendor.
2. Fertig. Unter den Ergebnissen stehen die Links "Spieltag als PDF-Dokument"
   und "Ligaspielplan als PDF-Dokument", unter dem Spielplan einer Mannschaft
   "Spielplan als PDF-Dokument" (Teamplan). In der Administration gibt es fuer
   den Hauptadmin den Menuepunkt "PDF-Design" mit allen Einstellungen.

Die Einstellungen stehen in config/pdf/cfg.txt (Vorgaben:
addon/pdf/config-default/cfg.txt). Ein Logo legt man als img/pdf/logo.png ab,
die Wappen kommen aus img/teams/small/.


Umstieg vom alten PDF-Addon (3.x)
---------------------------------

1. Den alten Ordner addon/pdf ganz loeschen, dann dieses ZIP entpacken.
2. Ebenfalls loeschen: addon/classlib/classes/pdf/ und lang/pdf/
3. Behalten: config/pdf/cfg.txt und img/pdf/. Die Einstellungen gelten
   weiter, auch die Farben.
4. Die Aenderungen am Code des LMO, die das alte Addon verlangte, entfallen.


Lizenz
------

GPL-3.0-or-later, siehe LICENSE. Dompdf: LGPL-2.1.
