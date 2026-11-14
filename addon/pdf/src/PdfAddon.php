<?php
/**
 * PDF-Addon fuer den Liga Manager Online
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

namespace Lmo\Addon\Pdf;

use Lmo\Addon\Addon;

/**
 * Das PDF-Addon. Links unter den Seiten und die Optionsseite folgen; die PDFs selbst liefern die
 * Einstiege pdf-*.php im Ordner des Addons.
 */
final class PdfAddon implements Addon
{
    public function name(): string
    {
        return 'pdf';
    }
}
