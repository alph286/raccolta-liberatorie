<?php
declare(strict_types=1);

/**
 * Generatore PDF minimale (solo testo Helvetica + immagini JPEG), senza dipendenze.
 * Coordinate in punti, origine in alto a sinistra, pagina A4.
 */
final class MiniPdf {
    private const W = 595.28, H = 841.89;
    // Larghezze Helvetica per i caratteri 32..126 (unita' per 1000)
    private const WIDTHS = [
        278,278,355,556,556,889,667,191,333,333,389,584,278,333,278,278,
        556,556,556,556,556,556,556,556,556,556,278,278,584,584,584,556,1015,
        667,667,722,722,667,611,778,722,278,500,667,556,833,722,778,667,778,722,667,611,722,667,944,667,667,611,
        278,278,278,469,556,333,
        556,556,500,556,556,278,556,556,222,222,500,222,833,556,556,556,556,333,500,278,556,500,722,500,500,500,
        334,260,334,584,
    ];
    private array $pages = [];
    private array $images = [];   // [jpegData, w, h]
    private int $cur = -1;

    public function addPage(): void { $this->pages[] = ''; $this->cur = count($this->pages) - 1; }
    public function pageCount(): int { return count($this->pages); }

    public static function enc(string $s): string {
        $c = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $s);
        $c = $c === false ? $s : $c;
        return str_replace(['\\', '(', ')', "\r"], ['\\\\', '\\(', '\\)', ''], $c);
    }
    public static function width(string $s, float $size, bool $bold = false): float {
        $c = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $s);
        $c = $c === false ? $s : $c;
        $w = 0;
        for ($i = 0, $n = strlen($c); $i < $n; $i++) {
            $o = ord($c[$i]);
            $w += ($o >= 32 && $o <= 126) ? self::WIDTHS[$o - 32] : 556;
        }
        return $w * $size / 1000 * ($bold ? 1.08 : 1.0);
    }
    /** Spezza il testo in righe che stanno in $maxW. */
    public static function wrap(string $text, float $size, float $maxW, bool $bold = false): array {
        $lines = [];
        foreach (preg_split('/\R/u', $text) as $para) {
            if (trim($para) === '') { $lines[] = ''; continue; }
            $line = '';
            foreach (preg_split('/\s+/u', trim($para)) as $word) {
                $try = $line === '' ? $word : $line . ' ' . $word;
                if ($line !== '' && self::width($try, $size, $bold) > $maxW) { $lines[] = $line; $line = $word; }
                else $line = $try;
            }
            $lines[] = $line;
        }
        return $lines;
    }
    public function text(float $x, float $y, string $s, float $size = 10, bool $bold = false): void {
        $this->pages[$this->cur] .= sprintf("BT /%s %.2f Tf %.2f %.2f Td (%s) Tj ET\n",
            $bold ? 'F2' : 'F1', $size, $x, self::H - $y, self::enc($s));
    }
    public function rect(float $x, float $y, float $w, float $h, bool $fill = false): void {
        $this->pages[$this->cur] .= sprintf("%.2f %.2f %.2f %.2f re %s\n", $x, self::H - $y - $h, $w, $h, $fill ? 'f' : 'S');
    }
    public function line(float $x1, float $y1, float $x2, float $y2): void {
        $this->pages[$this->cur] .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $x1, self::H - $y1, $x2, self::H - $y2);
    }
    public function gray(float $g): void { $this->pages[$this->cur] .= sprintf("%.2f g %.2f G\n", $g, $g); }
    public function jpeg(string $data, float $x, float $y, float $w, float $h): void {
        $info = getimagesizefromstring($data);
        $this->images[] = [$data, $info[0], $info[1]];
        $n = count($this->images);
        $this->pages[$this->cur] .= sprintf("q %.2f 0 0 %.2f %.2f %.2f cm /Im%d Do Q\n", $w, $h, $x, self::H - $y - $h, $n);
    }
    public function output(): string {
        $objs = [];  // indice 1-based
        $add = function (string $body) use (&$objs): int { $objs[] = $body; return count($objs); };
        $add('<< /Type /Catalog /Pages 2 0 R >>');          // 1
        $add('PLACEHOLDER');                                 // 2 (Pages)
        $add('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>');       // 3
        $add('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>');  // 4
        $imgIds = [];
        foreach ($this->images as [$data, $w, $h]) {
            $imgIds[] = $add("<< /Type /XObject /Subtype /Image /Width $w /Height $h /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length "
                . strlen($data) . " >>\nstream\n" . $data . "\nendstream");
        }
        $xo = '';
        foreach ($imgIds as $i => $id) $xo .= '/Im' . ($i + 1) . " $id 0 R ";
        $kids = [];
        foreach ($this->pages as $content) {
            $cid = $add("<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream");
            $pid = $add(sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2f %.2f] /Contents %d 0 R /Resources << /Font << /F1 3 0 R /F2 4 0 R >> /XObject << %s>> >> >>',
                self::W, self::H, $cid, $xo));
            $kids[] = "$pid 0 R";
        }
        $objs[1] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        $out = "%PDF-1.4\n"; $offs = [];
        foreach ($objs as $i => $body) { $offs[$i + 1] = strlen($out); $out .= ($i + 1) . " 0 obj\n$body\nendobj\n"; }
        $xref = strlen($out);
        $out .= "xref\n0 " . (count($objs) + 1) . "\n0000000000 65535 f \n";
        foreach ($offs as $o) $out .= sprintf("%010d 00000 n \n", $o);
        $out .= "trailer\n<< /Size " . (count($objs) + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
        return $out;
    }
}

/** Costruisce il PDF della liberatoria firmata. $c = riga di consents, $firmaJpeg = dati JPEG della firma. */
function build_consent_pdf(array $c, string $firmaJpeg): string {
    $L = 50.0; $R = 595.28 - 50.0; $maxW = $R - $L; $bottom = 790.0;
    $pdf = new MiniPdf();
    $y = 0.0;
    $newPage = function () use ($pdf, &$y, $c, $L) {
        $pdf->addPage();
        $pdf->gray(0.0);
        $pdf->text($L, 40, 'Liberatoria n. ' . $c['id'] . ' - ' . $c['cognome'] . ' ' . $c['nome'], 8);
        $pdf->gray(0.6); $pdf->line($L, 46, 545, 46); $pdf->gray(0.0);
        $y = 72.0;
    };
    $write = function (string $s, float $size = 10, bool $bold = false, float $gap = 4) use ($pdf, &$y, $L, $maxW, $bottom, $newPage) {
        foreach (MiniPdf::wrap($s, $size, $maxW, $bold) as $ln) {
            if ($y + $size > $bottom) $newPage();
            if ($ln !== '') $pdf->text($L, $y, $ln, $size, $bold);
            $y += $size + $gap;
        }
    };
    $newPage();
    $write(cfg('ente', ''), 9, false, 2);
    $write($c['template_titolo'], 16, true, 8);
    $y += 6;

    $write('Dichiarante', 11, true, 4);
    $rows = [
        'Nome e cognome' => $c['nome'] . ' ' . $c['cognome'],
        'Nato/a'         => ($c['luogo_nascita'] ?: '-') . ' il ' . it_date($c['data_nascita']),
        'Codice fiscale' => $c['codice_fiscale'] ?: '-',
        'Residenza'      => $c['indirizzo'],
        'Email'          => $c['email'],
        'Telefono'       => $c['telefono'] ?: '-',
        'Progetto'       => $c['progetto'] ?: '-',
        'Luogo riprese'  => $c['luogo_riprese'] ?: '-',
        'Data riprese'   => $c['data_riprese'] ? it_date($c['data_riprese']) : '-',
    ];
    foreach ($rows as $k => $v) $write("$k: $v", 10, false, 3);
    $y += 8;

    $write('Testo della liberatoria (versione ' . $c['template_versione'] . ')', 11, true, 4);
    $write($c['testo_snapshot'], 9.5, false, 3);
    $y += 8;

    $write('Consensi espressi', 11, true, 4);
    $yn = fn($b) => $b ? 'SI' : 'NO';
    $write('[' . $yn($c['consenso_immagini']) . '] Uso di fotografie e video in cui compaio', 10, false, 3);
    $write('[' . $yn($c['consenso_audio']) . '] Uso delle registrazioni audio della mia voce', 10, false, 3);
    $write('[' . $yn($c['consenso_diffusione']) . '] Diffusione e pubblicazione dei materiali (web, social, stampa, eventi)', 10, false, 3);
    $write('[' . $yn($c['consenso_privacy']) . '] Presa visione dell\'informativa privacy (art. 13 GDPR)', 10, false, 3);
    $y += 14;

    if ($y + 130 > $bottom) $newPage();
    $write('Firma del dichiarante', 11, true, 4);
    $pdf->rect($L, $y, 220, 80);
    $pdf->jpeg($firmaJpeg, $L + 4, $y + 4, 212, 72);
    $y += 92;
    $write('Accettata il ' . date('d/m/Y \a\l\l\e H:i', strtotime($c['created_at'])) . ' - IP ' . $c['ip'], 8.5, false, 2);
    $write('Impronta SHA-256 del testo accettato: ' . $c['testo_hash'], 7, false, 2);
    if (!empty($c['operatore'])) $write('Raccolta dall\'operatore: ' . $c['operatore'], 8.5, false, 2);
    $write('Riferimento: ' . $c['token'], 7, false, 2);
    return $pdf->output();
}
