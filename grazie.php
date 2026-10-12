<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/bootstrap.php';
$tok = (string)($_GET['r'] ?? '');
$row = null;
if (preg_match('/^[a-f0-9]{32}$/', $tok)) {
    $st = db()->prepare('SELECT id, nome, mail_inviata FROM consents WHERE token = ?');
    $st->execute([$tok]);
    $row = $st->fetch();
}
page_head('Grazie');
?>
<main class="wrap">
<?php if ($row): ?>
  <h1>Grazie, <?= h($row['nome']) ?>!</h1>
  <p>La tua liberatoria (n. <?= (int)$row['id'] ?>) e' stata registrata.</p>
  <?php if ($row['mail_inviata']): ?><p>Ti abbiamo inviato una copia firmata via email.</p><?php endif; ?>
  <p><a class="btn secondary" href="<?= h(base_url()) ?>">Nuova liberatoria</a>
     <a class="btn" href="<?= h(base_url()) ?>pdf.php?r=<?= h($tok) ?>">Scarica la copia PDF</a></p>
<?php else: ?>
  <h1>Pagina non trovata</h1>
<?php endif; ?>
</main>
<?php page_foot();
