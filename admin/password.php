<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/_nav.php';
$u = require_admin();
$msg = $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $st = db()->prepare('SELECT password_hash FROM admins WHERE id = ?'); $st->execute([$u['id']]);
    $new = (string)($_POST['new'] ?? '');
    if (!password_verify((string)($_POST['old'] ?? ''), (string)$st->fetchColumn())) $err = 'Password attuale errata.';
    elseif (strlen($new) < 10) $err = 'La nuova password deve avere almeno 10 caratteri.';
    elseif ($new !== ($_POST['new2'] ?? '')) $err = 'Le password non coincidono.';
    else { db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]); $msg = 'Password aggiornata.'; }
}
page_head('Password');
?>
<main class="wrap" style="max-width:480px">
<?php admin_nav($u); ?>
<h1>Cambia password</h1>
<?php if ($msg): ?><div class="ok"><?= h($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert"><?= h($err) ?></div><?php endif; ?>
<form method="post" class="card"><?= csrf_field() ?>
<label>Password attuale<input type="password" name="old" required autocomplete="current-password"></label>
<label style="margin-top:10px">Nuova password (min. 10 caratteri)<input type="password" name="new" required autocomplete="new-password"></label>
<label style="margin-top:10px">Ripeti nuova password<input type="password" name="new2" required autocomplete="new-password"></label>
<p><button class="btn">Aggiorna</button></p></form>
</main>
<?php page_foot();
