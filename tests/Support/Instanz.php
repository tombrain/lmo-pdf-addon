<?php

declare(strict_types=1);

namespace Lmo\PdfAddon\Tests\Support;

use RuntimeException;

/**
 * Eine LMO-Installation fuer die Tests in einem eigenen Ordner: der LMO aus vendor/lmo/lmo_php8, die
 * Testligen unter ligen/golden und auf Wunsch das alte Addon (tests/altes-addon) oder das neue
 * (addon/pdf). Skripte laufen als eigener PHP-Prozess (tests/bin/aufruf.php).
 */
final class Instanz
{
    private function __construct(private string $ordner)
    {
    }

    /** Eine frische Installation */
    public static function neu(): self
    {
        $wurzel = dirname(__DIR__, 2);
        $ordner = sys_get_temp_dir() . '/lmo-pdf-test-' . getmypid() . '-' . bin2hex(random_bytes(3));
        self::kopiere($wurzel . '/vendor/lmo/lmo_php8/lmo', $ordner . '/lmo');
        // init.php sucht vendor/autoload.php neben lmo/
        if (!symlink($wurzel . '/vendor', $ordner . '/vendor')) {
            throw new RuntimeException('vendor nicht verlinkt');
        }
        self::kopiere(__DIR__ . '/../fixtures/ligen', $ordner . '/lmo/ligen/golden');
        mkdir($ordner . '/sitzungen');

        return new self($ordner);
    }

    /**
     * Das alte Addon (Stand henshingly) in die Installation kopieren. Wie in seiner Anleitung bindet
     * die Classlib dafuer die PDF-Klassen ein (im LMO waren die Zeilen auskommentiert).
     */
    public function mitAltemAddon(): self
    {
        self::kopiere(dirname(__DIR__) . '/altes-addon', $this->lmo());
        file_put_contents($this->lmo() . '/addon/classlib/ini.php', "<?php\n"
            . "require_once(PATH_TO_ADDONDIR . '/classlib/classes/pdf/Cezpdf.php');\n"
            . "include_once(PATH_TO_ADDONDIR . '/classlib/classes/pdf/extensions/CezTableImage.php');\n?>", FILE_APPEND);
        // das Logo laedt es ueber die Adresse des LMO, die es im Test nicht gibt; fuer den Inhalt unwichtig
        $cfg = $this->lmo() . '/config/pdf/cfg.txt';
        // ebenso Logo und Wappen
        file_put_contents($cfg, (string) preg_replace('/^(lmo_show_pdfimg|lmo_pdf_\w*icon)=1(\r?)$/m', '${1}=0$2',(string) file_get_contents($cfg)));

        return $this;
    }

    /** Das neue Addon (addon/pdf, mit seiner Laufzeit in vendor) in die Installation kopieren */
    public function mitNeuemAddon(): self
    {
        self::kopiere(dirname(__DIR__, 2) . '/addon/pdf', $this->lmo() . '/addon/pdf');

        return $this;
    }

    /**
     * Die Konfiguration des Addons (config/pdf/cfg.txt) setzen; ersetzt die bisherige.
     *
     * @param array<string, string> $werte
     */
    public function mitKonfiguration(array $werte): self
    {
        if (!is_dir($this->lmo() . '/config/pdf')) {
            mkdir($this->lmo() . '/config/pdf', 0777, true);
        }
        $zeilen = array_map(static fn (string $k, string $v): string => $k . '=' . $v . "\n", array_keys($werte), $werte);
        file_put_contents($this->lmo() . '/config/pdf/cfg.txt', implode('', $zeilen));

        return $this;
    }

    public function lmo(): string
    {
        return $this->ordner . '/lmo';
    }

    /**
     * Ruft ein Skript auf wie ein Webserver (GET).
     *
     * @return string die Ausgabe
     */
    public function aufruf(string $skript, string $abfrage = ''): string
    {
        $befehl = [PHP_BINARY, '-d', 'display_errors=stderr', '-d', 'error_reporting=' . E_ALL, '-d', 'date.timezone=Europe/Berlin',
            '-d', 'session.save_path=' . $this->ordner . '/sitzungen', dirname(__DIR__) . '/bin/aufruf.php', $this->lmo(), $skript, $abfrage];
        $prozess = proc_open($befehl, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $roehren, $this->lmo());
        if (!is_resource($prozess)) {
            throw new RuntimeException('Aufruf nicht moeglich: ' . $skript);
        }
        $ausgabe = (string) stream_get_contents($roehren[1]);
        $fehler = (string) stream_get_contents($roehren[2]);
        fclose($roehren[1]);
        fclose($roehren[2]);
        proc_close($prozess);
        // das alte Addon schreibt die Dauer ("Page Building ...") auf stderr; Fehler sind nur PHP-Meldungen
        if (preg_match('/(Warning|Notice|Deprecated|Fatal error|Parse error):/', $fehler) === 1) {
            throw new RuntimeException('PHP-Meldungen bei ' . $skript . '?' . $abfrage . ":\n" . $fehler);
        }

        return $ausgabe;
    }

    public function __destruct()
    {
        exec('rm -rf ' . escapeshellarg($this->ordner));
    }

    private static function kopiere(string $von, string $nach): void
    {
        if (!is_dir($nach)) {
            mkdir($nach, 0777, true);
        }
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($von, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $eintrag) {
            $ziel = $nach . '/' . substr((string) $eintrag->getPathname(), strlen($von) + 1);
            if ($eintrag->isDir()) {
                if (!is_dir($ziel)) {
                    mkdir($ziel, 0777, true);
                }
            } else {
                copy((string) $eintrag->getPathname(), $ziel);
            }
        }
    }
}
