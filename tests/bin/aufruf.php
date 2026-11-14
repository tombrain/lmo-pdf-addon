<?php
/**
 * Kindprozess: ruft ein Skript der Testinstanz auf wie ein Webserver (GET) und gibt seine Ausgabe
 * auf stdout aus. Aufruf: php aufruf.php <Ordner des LMO> <Skript relativ dazu> <Abfrage a=1&b=2>
 *
 * Bewusst im globalen Bereich: die Skripte des alten Addons erwarten ihn.
 */
$aufruf_lmo = $argv[1];
$aufruf_skript = $argv[2];
parse_str($argv[3] ?? '', $_GET);
$_POST = [];
$_COOKIE = [];
$_REQUEST = $_GET;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'lmo.test';
$_SERVER['SCRIPT_NAME'] = '/lmo/' . $aufruf_skript;
$_SERVER['PHP_SELF'] = '/lmo/' . $aufruf_skript;
$_SERVER['REQUEST_URI'] = '/lmo/' . $aufruf_skript . (($argv[3] ?? '') !== '' ? '?' . $argv[3] : '');
$_SERVER['SCRIPT_FILENAME'] = $aufruf_lmo . '/' . $aufruf_skript;
$_SERVER['QUERY_STRING'] = $argv[3] ?? '';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de';

chdir(dirname($aufruf_lmo . '/' . $aufruf_skript));
require $aufruf_lmo . '/' . $aufruf_skript;
