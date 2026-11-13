<?php

declare(strict_types=1);

// Entwicklung: LMO, PHPUnit und PDF-Parser; dazu die Laufzeit des Addons (Dompdf) aus addon/pdf/vendor
require __DIR__ . '/../vendor/autoload.php';

$laufzeit = __DIR__ . '/../addon/pdf/vendor/autoload.php';
if (!is_file($laufzeit)) {
    fwrite(STDERR, "addon/pdf/vendor fehlt: composer install im Ordner addon/pdf ausfuehren\n");
    exit(1);
}
require $laufzeit;
