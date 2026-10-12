<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/_nav.php';
$u = require_admin();
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = (string)($_POST['act'] ?? '');
    if ($act === 'add') {
        $name = trim((string)($_POST['username'] ?? '')); $pass = (string)($_POST['password'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_.-]{3,60}$/', $name)) $err = 'Utente: 3-60 caratteri (lettere, numeri, . _ -).';
        elseif (strlen($pass) < 10) $err = 'La password deve avere almeno 10 caratteri.';
        else {
            $st = db()->prepare('SELECT COUNT(*) FROM admins WHERE username = ?'); $st->execute([$name]);
            if ((int)$st->fetchColumn() > 0) $err = 'Esiste gia\' un utente con questo nome.';
            else {
                db()->prepare('INSERT INTO admins (username, password_hash, created_at) VALUES (?, ?, NOW())')
                    ->execute([$name, password_hash($pass, PASSWORD_DEFAULT)]);
                $msg = "Utente \"$name\" creato.";
            }
        }
    } elseif ($act === 'reset') {
        $id = (int)($_POST['id'] ?? 0); $pass = (string)($_POST['password'] ?? '');
        if (strlen($pass) < 10) $err = 'La password deve avere almeno 10 caratteri.';
        else {
            db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([password_hash($pass, PASSWORD_DEFAULT), $id]);
            $msg = 'Password reimpostata.';
        }
    } elseif ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$u['id']) $err = 'Non puoi eliminare il tuo stesso utente.';
        else { db()->prepare('DELETE FROM admins WHERE id = ?')->execute([$id]); $msg = 'Utente eliminato.'; }
    }
}
$users = db()->query('SELECT id, username, created_at FROM admins ORDER BY username')->fetchAll();
page_head('Utenti');
?>
<main class="wrap">
<?php admin_nav($u); ?>
<h1>Utenti amministratori</h1>
<?php if ($msg): ?><div class="ok"><?= h($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert"><?= h($err) ?></div><?php endif; ?>
<table><tr><th>Utente</th><th>Creato il</th><th>Reimposta password</th><th></th></tr>
<?php foreach ($users as $r): ?>
<tr><td><?= h($r['username']) ?><?= (int)$r['id'] === (int)$u['id'] ? ' (tu)' : '' ?></td>
<td><?= h(date('d/m/Y', strtotime($r['created_at']))) ?></td>
<td><form method="post" style="display:flex;gap:6px"><?= csrf_field() ?><input type="hidden" name="act" value="reset"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
<input type="password" name="password" placeholder="nuova password" autocomplete="new-password" style="margin:0"><button class="btn small secondary">Imposta</button></form></td>
<td><?php if ((int)$r['id'] !== (int)$u['id']): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn small secondary">Elimina</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></table>
<h2>Nuovo utente</h2>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="act" value="add">
<label>Nome utente<input name="username" required autocomplete="off"></label>
<label style="margin-top:10px">Password (min. 10 caratteri)<input type="password" name="password" required autocomplete="new-password"></label>
<p><button class="btn">Crea utente</button></p></form>
</main>
<?php page_foot();
