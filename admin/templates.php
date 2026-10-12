<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/_nav.php';
$u = require_admin();
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = (string)($_POST['act'] ?? '');
    if ($act === 'save') {
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(trim((string)($_POST['slug'] ?? ''))));
        $titolo = trim((string)($_POST['titolo'] ?? '')); $testo = trim((string)($_POST['testo'] ?? ''));
        if ($slug === '' || $titolo === '' || $testo === '') $err = 'Slug, titolo e testo sono obbligatori.';
        else {
            // Ogni modifica crea una NUOVA versione: le liberatorie gia' firmate restano legate al testo accettato.
            $st = db()->prepare('SELECT COALESCE(MAX(versione),0) FROM templates WHERE slug = ?'); $st->execute([$slug]);
            $ver = (int)$st->fetchColumn() + 1;
            db()->beginTransaction();
            db()->prepare('UPDATE templates SET attiva = 0 WHERE slug = ?')->execute([$slug]);
            db()->prepare('INSERT INTO templates (slug,titolo,testo,versione,attiva,created_at) VALUES (?,?,?,?,1,NOW())')->execute([$slug, $titolo, $testo, $ver]);
            db()->commit();
            $msg = "Salvata la versione $ver di \"$titolo\".";
        }
    } elseif ($act === 'disable') {
        db()->prepare('UPDATE templates SET attiva = 0 WHERE slug = ?')->execute([(string)($_POST['slug'] ?? '')]);
        $msg = 'Modello disattivato.';
    }
}
$edit = null;
if (!empty($_GET['edit'])) { $st = db()->prepare('SELECT * FROM templates WHERE slug = ? ORDER BY versione DESC LIMIT 1'); $st->execute([(string)$_GET['edit']]); $edit = $st->fetch() ?: null; }
$list = db()->query('SELECT t.* FROM templates t JOIN (SELECT slug, MAX(versione) v FROM templates GROUP BY slug) m ON m.slug=t.slug AND m.v=t.versione ORDER BY t.slug')->fetchAll();
$base = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . base_url();
page_head('Modelli');
?>
<main class="wrap">
<?php admin_nav($u); ?>
<h1>Modelli di liberatoria</h1>
<?php if ($msg): ?><div class="ok"><?= h($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert"><?= h($err) ?></div><?php endif; ?>
<table><tr><th>Slug / link</th><th>Titolo</th><th>Versione</th><th>Stato</th><th></th></tr>
<?php foreach ($list as $t): ?>
<tr><td><code><?= h($t['slug']) ?></code><br><small><?= h($base) ?>?t=<?= h($t['slug']) ?></small></td><td><?= h($t['titolo']) ?></td><td>v<?= (int)$t['versione'] ?></td>
<td><?= $t['attiva'] ? 'attivo' : 'disattivo' ?></td>
<td><a class="btn small" href="?edit=<?= h($t['slug']) ?>">Modifica</a>
<?php if ($t['attiva']): ?><form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="act" value="disable"><input type="hidden" name="slug" value="<?= h($t['slug']) ?>"><button class="btn small secondary">Disattiva</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></table>
<h2><?= $edit ? 'Modifica (crea nuova versione)' : 'Nuovo modello' ?></h2>
<form method="post" class="card">
  <?= csrf_field() ?><input type="hidden" name="act" value="save">
  <label>Slug (usato nel link, es. accademia)<input name="slug" value="<?= h($edit['slug'] ?? '') ?>" <?= $edit ? 'readonly' : '' ?> required></label>
  <label style="margin-top:10px">Titolo<input name="titolo" value="<?= h($edit['titolo'] ?? '') ?>" required></label>
  <label style="margin-top:10px">Testo della liberatoria<textarea name="testo" required><?= h($edit['testo'] ?? '') ?></textarea></label>
  <p><button class="btn">Salva come nuova versione</button></p>
</form>
</main>
<?php page_foot();
