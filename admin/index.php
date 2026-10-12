<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/_nav.php';
$u = require_admin();

$q = trim((string)($_GET['q'] ?? ''));
$tid = (int)($_GET['tpl'] ?? 0);
$from = (string)($_GET['dal'] ?? ''); $to = (string)($_GET['al'] ?? '');
$where = ['1=1']; $args = [];
if ($q !== '') {
    $where[] = '(nome LIKE ? OR cognome LIKE ? OR email LIKE ? OR progetto LIKE ? OR codice_fiscale LIKE ?)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    array_push($args, $like, $like, $like, $like, $like);
}
if ($tid) { $where[] = 'template_id IN (SELECT id FROM templates WHERE slug = (SELECT slug FROM templates WHERE id = ?))'; $args[] = $tid; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) { $where[] = 'created_at >= ?'; $args[] = $from . ' 00:00:00'; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   { $where[] = 'created_at <= ?'; $args[] = $to . ' 23:59:59'; }
$W = implode(' AND ', $where);

if (isset($_GET['csv'])) {
    $st = db()->prepare("SELECT * FROM consents WHERE $W ORDER BY id DESC"); $st->execute($args);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="liberatorie-' . date('Ymd') . '.csv"');
    $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
    $cols = ['id','created_at','template_titolo','template_versione','nome','cognome','data_nascita','luogo_nascita','codice_fiscale','indirizzo','email','telefono','progetto','luogo_riprese','data_riprese','consenso_immagini','consenso_audio','consenso_diffusione','consenso_privacy','ip'];
    fputcsv($o, $cols, ';');
    // neutralizza formule Excel
    $safe = fn($v) => is_string($v) && $v !== '' && strpos('=+-@', $v[0]) !== false ? "'" . $v : $v;
    while ($r = $st->fetch()) fputcsv($o, array_map(fn($c) => $safe($r[$c]), $cols), ';');
    exit;
}

$per = 25; $page = max(1, (int)($_GET['p'] ?? 1));
$st = db()->prepare("SELECT COUNT(*) FROM consents WHERE $W"); $st->execute($args);
$total = (int)$st->fetchColumn(); $pages = max(1, (int)ceil($total / $per));
$st = db()->prepare("SELECT id,created_at,nome,cognome,email,progetto,template_titolo,mail_inviata FROM consents WHERE $W ORDER BY id DESC LIMIT $per OFFSET " . (($page - 1) * $per));
$st->execute($args); $rows = $st->fetchAll();
$tpls = db()->query('SELECT MAX(id) id, titolo FROM templates GROUP BY slug, titolo ORDER BY titolo')->fetchAll();
$qs = fn(array $x) => h(http_build_query(array_filter(array_merge($_GET, $x), fn($v) => $v !== '' && $v !== null)));

page_head('Liberatorie');
?>
<main class="wrap wide">
<?php admin_nav($u); ?>
<h1>Liberatorie raccolte (<?= $total ?>)</h1>
<form class="filters" method="get">
  <input name="q" value="<?= h($q) ?>" placeholder="Cerca nome, email, progetto...">
  <select name="tpl"><option value="">Tutti i modelli</option>
    <?php foreach ($tpls as $t): ?><option value="<?= (int)$t['id'] ?>"<?= $tid === (int)$t['id'] ? ' selected' : '' ?>><?= h($t['titolo']) ?></option><?php endforeach; ?>
  </select>
  <input type="date" name="dal" value="<?= h($from) ?>"> <input type="date" name="al" value="<?= h($to) ?>">
  <button class="btn small">Filtra</button>
  <a class="btn small secondary" href="?<?= $qs(['csv' => 1]) ?>">Esporta CSV</a>
</form>
<div style="overflow-x:auto"><table>
<tr><th>N.</th><th>Data</th><th>Nome</th><th>Email</th><th>Progetto</th><th>Modello</th><th>Mail</th><th></th></tr>
<?php foreach ($rows as $r): ?>
<tr><td><?= (int)$r['id'] ?></td><td><?= h(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td>
<td><?= h($r['cognome'] . ' ' . $r['nome']) ?></td><td><?= h($r['email']) ?></td><td><?= h($r['progetto']) ?></td>
<td><?= h($r['template_titolo']) ?></td><td><?= $r['mail_inviata'] ? 'si' : 'no' ?></td>
<td><a class="btn small" href="view.php?id=<?= (int)$r['id'] ?>">Apri</a></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="8">Nessuna liberatoria trovata.</td></tr><?php endif; ?>
</table></div>
<p>Pagina <?= $page ?> di <?= $pages ?>
<?php if ($page > 1): ?> <a href="?<?= $qs(['p' => $page - 1]) ?>">&laquo; precedente</a><?php endif; ?>
<?php if ($page < $pages): ?> <a href="?<?= $qs(['p' => $page + 1]) ?>">successiva &raquo;</a><?php endif; ?></p>
</main>
<?php page_foot();
