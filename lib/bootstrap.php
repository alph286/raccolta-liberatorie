<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
$configFile = ROOT . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Configurazione mancante: copiare config.sample.php in config.php.');
}
$GLOBALS['cfg'] = require $configFile;
date_default_timezone_set($GLOBALS['cfg']['timezone'] ?? 'Europe/Rome');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => (bool)($GLOBALS['cfg']['https_only'] ?? true),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; frame-ancestors 'none'; form-action 'self'");

function cfg(string $k, $default = null) { return $GLOBALS['cfg'][$k] ?? $default; }

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $c = $GLOBALS['cfg']['db'];
        $pdo = new PDO(
            "mysql:host={$c['host']};dbname={$c['name']};charset=utf8mb4",
            $c['user'], $c['pass'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        $pdo->exec("SET time_zone = '" . date('P') . "'");
    }
    return $pdo;
}

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">'; }
function csrf_check(): void {
    $ok = isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
    if (!$ok) { http_response_code(400); exit('Sessione scaduta o richiesta non valida. Ricarica la pagina e riprova.'); }
}

function client_ip(): string { return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45); }

function it_date(?string $ymd): string {
    if (!$ymd) return '';
    $d = DateTime::createFromFormat('Y-m-d', substr($ymd, 0, 10));
    return $d ? $d->format('d/m/Y') : '';
}

function page_head(string $title, string $extra = ''): void {
    echo '<!doctype html><html lang="it"><head><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>' . h($title) . '</title>'
       . '<link rel="stylesheet" href="' . h(base_url()) . 'assets/style.css">' . $extra . '</head><body>';
}
function page_foot(): void { echo '</body></html>'; }

// URL base dell'app (sempre con slash finale), calcolato dalla posizione di bootstrap.
function base_url(): string {
    $doc = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $root = realpath(ROOT) ?: ROOT;
    $rel = ($doc !== '' && str_starts_with($root, $doc)) ? substr($root, strlen($doc)) : '';
    return rtrim(str_replace('\\', '/', $rel), '/') . '/';
}
