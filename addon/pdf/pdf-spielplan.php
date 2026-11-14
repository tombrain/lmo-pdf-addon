<?php
/**
 * PDF-Addon fuer den Liga Manager Online
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

// Spielplan als PDF (Lmo\Addon\Pdf\Spielplanpdf): pdf-spielplan.php?file=<liga>
require_once __DIR__ . '/../../init.php';
require_once __DIR__ . '/vendor/autoload.php';

$lmo_pdf = (new \Lmo\Addon\Pdf\Spielplanpdf(
    new \Lmo\Web\LigaLader(PATH_TO_LMO . '/' . $dirliga, (string)$defdateformat),
    is_array($cfgarray['pdf'] ?? null) ? $cfgarray['pdf'] : array(),
    $text,
    __DIR__,
    URL_TO_IMGDIR
))->antwort(is_string($_GET['file'] ?? null) ? $_GET['file'] : '', date('d.m.Y - H:i'));
foreach ($lmo_pdf['kopf'] as $lmo_pdf_kopf) {
    header($lmo_pdf_kopf);
}
echo $lmo_pdf['inhalt'];
