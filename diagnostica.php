<?php
// Script di diagnostica: controlla PHP, estensioni, config, database e cartelle. CANCELLARLO dopo l'uso.
ini_set('display_errors', '1'); error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');
function ok($b, $t, $ko = '') { echo ($b ? '[OK]   ' : '[ERRORE] ') . $t . ($b ? '' : ($ko ? " -> $ko" : '')) . "\n"; return $b; }

echo "Diagnostica liberatorie\n=======================\n";
ok(version_compare(PHP_VERSION, '8.1.0', '>='), 'PHP ' . PHP_VERSION, 'serve PHP 8.1 o superiore: cambialo dal pannello Serverplan per questo dominio');
foreach (['pdo_mysql', 'gd', 'mbstring', 'iconv', 'session'] as $e) ok(extension_loaded($e), "estensione $e", 'attivala dal pannello (Seleziona versione PHP / Estensioni)');
ok(function_exists('mail'), 'funzione mail() disponibile');

$cfgFile = __DIR__ . '/config.php';
if (!ok(is_file($cfgFile), 'config.php presente', 'copia config.sample.php in config.php e compilalo')) exit;
$cfg = @include $cfgFile;
if (!ok(is_array($cfg) && isset($cfg['db']), 'config.php valido (restituisce un array con "db")', 'controlla che inizi con <?php e finisca con ];')) exit;

try {
    $c = $cfg['db'];
    $pdo = new PDO("mysql:host={$c['host']};dbname={$c['name']};charset=utf8mb4", $c['user'], $c['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    ok(true, 'connessione al database');
    $t = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    ok(in_array('consents', $t, true), 'tabelle create (' . count($t) . ' trovate)', 'esegui install.php');
} catch (Throwable $e) {
    ok(false, 'connessione al database', $e->getMessage());
}

$dir = rtrim($cfg['storage_dir'] ?? '', '/') ?: __DIR__ . '/storage';
echo "storage: $dir\n";
foreach (['', '/pdf', '/firme'] as $s) {
    if (!is_dir($dir . $s)) @mkdir($dir . $s, 0750, true);
    ok(is_dir($dir . $s) && is_writable($dir . $s), "cartella $dir$s scrivibile", 'crea la cartella e dai permessi 755/750 (o 775)');
}
ok(!is_file(__DIR__ . '/install.php') || is_file(__DIR__ . '/installed.lock'), 'install.php rimosso o gia\' eseguito', 'dopo l\'installazione cancella install.php');
echo "\nFine. Cancella diagnostica.php dal server.\n";
