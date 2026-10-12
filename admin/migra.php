<?php
declare(strict_types=1);
// Aggiornamento del database per chi ha installato una versione precedente. Idempotente: si puo' rilanciare.
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/_nav.php';
$u = require_admin();
$log = [];
$has = static fn(string $col): bool => (bool)db()->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'consents' AND COLUMN_NAME = " . db()->quote($col))->fetchColumn();
if (!$has('operatore')) {
    db()->exec('ALTER TABLE consents ADD COLUMN operatore VARCHAR(60) NULL AFTER ip');
    $log[] = 'Aggiunta la colonna "operatore".';
} else $log[] = 'Colonna "operatore" gia\' presente.';
page_head('Aggiornamento database');
echo '<main class="wrap">'; admin_nav($u);
echo '<h1>Aggiornamento database</h1><div class="ok">' . implode('<br>', array_map('h', $log)) . '</div><p>Fatto: puoi cancellare questo file (<code>admin/migra.php</code>).</p></main>';
page_foot();
