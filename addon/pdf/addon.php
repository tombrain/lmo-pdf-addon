<?php
/**
 * PDF-Addon fuer den Liga Manager Online
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

if (!defined('PATH_TO_LMO')) {
    exit;  // kein Direktaufruf: diese Datei wird nur ueber LMO eingebunden
}

// PDF-Dokumente zu Spieltag, Spielplan und Teamplan (Lmo\Addon\Pdf\PdfAddon)
return new \Lmo\Addon\Pdf\PdfAddon();
