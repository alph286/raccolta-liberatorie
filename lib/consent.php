<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/pdf.php';
require_once __DIR__ . '/mail.php';

function find_template(?string $slug): ?array {
    if ($slug) $st = db()->prepare('SELECT * FROM templates WHERE slug = ? AND attiva = 1 ORDER BY versione DESC LIMIT 1');
    else       $st = db()->prepare('SELECT * FROM templates WHERE attiva = 1 ORDER BY id ASC LIMIT 1');
    $st->execute($slug ? [$slug] : []);
    return $st->fetch() ?: null;
}

/** Valida l'input del modulo. Ritorna [dati puliti, errori]. */
function validate_consent(array $in): array {
    $e = []; $d = [];
    $t = fn($k, $max) => mb_substr(trim((string)($in[$k] ?? '')), 0, $max);
    foreach (['nome' => 100, 'cognome' => 100, 'luogo_nascita' => 120, 'indirizzo' => 255, 'email' => 150] as $k => $m) {
        $d[$k] = $t($k, $m);
        if ($d[$k] === '') $e[$k] = 'Campo obbligatorio';
    }
    $d['telefono'] = $t('telefono', 40);
    $d['progetto'] = $t('progetto', 200);
    $d['luogo_riprese'] = $t('luogo_riprese', 200);
    if (!isset($e['email']) && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $e['email'] = 'Email non valida';

    $d['codice_fiscale'] = strtoupper(preg_replace('/\s+/', '', $t('codice_fiscale', 16)));
    if ($d['codice_fiscale'] !== '' && !preg_match('/^[A-Z0-9]{16}$/', $d['codice_fiscale'])) $e['codice_fiscale'] = 'Codice fiscale non valido';
    if ($d['codice_fiscale'] === '') $d['codice_fiscale'] = null;

    $nasc = DateTime::createFromFormat('!Y-m-d', (string)($in['data_nascita'] ?? ''));
    if (!$nasc || $nasc > new DateTime('today')) $e['data_nascita'] = 'Data non valida';
    else {
        $d['data_nascita'] = $nasc->format('Y-m-d');
        if ($nasc->diff(new DateTime('today'))->y < 18) $e['data_nascita'] = 'La liberatoria puo\' essere firmata solo da maggiorenni';
    }
    $dr = (string)($in['data_riprese'] ?? '');
    if ($dr !== '') {
        $x = DateTime::createFromFormat('!Y-m-d', $dr);
        if (!$x) $e['data_riprese'] = 'Data non valida'; else $d['data_riprese'] = $x->format('Y-m-d');
    }
    $d['data_riprese'] ??= null;

    foreach (['consenso_immagini', 'consenso_audio', 'consenso_diffusione', 'consenso_privacy'] as $k) {
        $d[$k] = !empty($in[$k]) ? 1 : 0;
    }
    // Privacy e maggiore eta' sono sempre obbligatori; gli altri consensi devono essere espressi (si/no) ma almeno uno deve essere dato.
    if (!$d['consenso_privacy']) $e['consenso_privacy'] = 'Necessaria per procedere';
    if (empty($in['maggiorenne'])) $e['maggiorenne'] = 'Necessaria per procedere';
    if (!$d['consenso_immagini'] && !$d['consenso_audio']) $e['consenso_immagini'] = 'Seleziona almeno il consenso a immagini o audio';
    return [$d, $e];
}

/** Decodifica la firma (data URL PNG) e ritorna [pngData, jpegData] su sfondo bianco, o null se non valida. */
function decode_signature(string $dataUrl): ?array {
    if (!preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $dataUrl, $m) || strlen($m[1]) > 700000) return null;
    $png = base64_decode($m[1], true);
    if ($png === false) return null;
    $src = @imagecreatefromstring($png);
    if (!$src) return null;
    $w = imagesx($src); $h = imagesy($src);
    if ($w < 50 || $h < 20 || $w > 2000 || $h > 1000) return null;
    $dst = imagecreatetruecolor($w, $h);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    imagecopy($dst, $src, 0, 0, 0, 0, $w, $h);
    // verifica che ci sia effettivamente del tratto: campiona i pixel
    $ink = 0;
    for ($x = 0; $x < $w; $x += 3) for ($y = 0; $y < $h; $y += 3) if ((imagecolorat($dst, $x, $y) & 0xFF) < 128) $ink++;
    if ($ink < 15) return null;
    ob_start(); imagepng($dst); $outPng = ob_get_clean();
    ob_start(); imagejpeg($dst, null, 90); $jpeg = ob_get_clean();
    return [$outPng, $jpeg];
}

/** Salva la liberatoria, genera PDF e invia le mail. Ritorna il token. */
function save_consent(array $tpl, array $d, string $png, string $jpeg): string {
    $token = bin2hex(random_bytes(16));
    $pdo = db();
    $firmaFile = $token . '.png';
    file_put_contents(storage_path('firme') . '/' . $firmaFile, $png);
    $row = $d + [
        'token' => $token, 'template_id' => $tpl['id'], 'template_titolo' => $tpl['titolo'],
        'template_versione' => $tpl['versione'], 'testo_snapshot' => $tpl['testo'],
        'testo_hash' => hash('sha256', $tpl['testo']), 'firma_file' => $firmaFile,
        'ip' => client_ip(), 'user_agent' => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        'created_at' => date('Y-m-d H:i:s'),
    ];
    $cols = array_keys($row);
    $pdo->prepare('INSERT INTO consents (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')')
        ->execute(array_values($row));
    $row['id'] = (int)$pdo->lastInsertId();

    $pdfData = build_consent_pdf($row, $jpeg);
    $pdfFile = $token . '.pdf';
    file_put_contents(storage_path('pdf') . '/' . $pdfFile, $pdfData);
    $pdo->prepare('UPDATE consents SET pdf_file = ? WHERE id = ?')->execute([$pdfFile, $row['id']]);

    $ok = false;
    $name = 'liberatoria-' . $row['id'] . '.pdf';
    $body = "Gentile {$d['nome']} {$d['cognome']},\n\nin allegato la copia della liberatoria \"{$tpl['titolo']}\" da lei accettata il "
          . date('d/m/Y H:i') . ".\n\n" . cfg('ente', '');
    if (cfg('mail_intervistato', true)) $ok = send_pdf_mail($d['email'], 'Copia della liberatoria firmata', $body, $pdfData, $name);
    if ($r = cfg('mail_redazione', '')) {
        $ok = send_pdf_mail($r, "Nuova liberatoria: {$d['cognome']} {$d['nome']}", "Nuova liberatoria n. {$row['id']} ricevuta.", $pdfData, $name) || $ok;
    }
    if ($ok) $pdo->prepare('UPDATE consents SET mail_inviata = 1 WHERE id = ?')->execute([$row['id']]);
    return $token;
}
