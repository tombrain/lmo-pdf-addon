<?php
/**
 * PDF-Addon fuer den Liga Manager Online
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

// Teamplan als PDF (Lmo\Addon\Pdf\Teamplanpdf): pdf-teamplan.php?file=<liga>&selteam=<mannschaft>
require_once __DIR__ . '/../../init.php';
require_once __DIR__ . '/vendor/autoload.php';

$lmo_pdf = (new \Lmo\Addon\Pdf\Teamplanpdf(
    new \Lmo\Web\LigaLader(PATH_TO_LMO . '/' . $dirliga, (string)$defdateformat),
    is_array($cfgarray['pdf'] ?? null) ? $cfgarray['pdf'] : array(),
    $text,
    __DIR__,
    URL_TO_IMGDIR
))->antwort(is_string($_GET['file'] ?? null) ? $_GET['file'] : '', (int)($_GET['selteam'] ?? 0), date('d.m.Y - H:i'));
foreach ($lmo_pdf['kopf'] as $lmo_pdf_kopf) {
    header($lmo_pdf_kopf);
}
echo $lmo_pdf['inhalt'];
