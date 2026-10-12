<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
// Se arrivi dal modulo (?form=1[&t=slug]) dopo il login torni al modulo.
$slug = preg_replace('/[^a-z0-9-]/', '', strtolower((string)($_GET['t'] ?? $_POST['t'] ?? '')));
$toForm = !empty($_GET['form']) || !empty($_POST['form']);
$dest = $toForm ? base_url() . ($slug !== '' ? '?t=' . $slug : '') : 'index.php';
if (admin_user()) { header('Location: ' . $dest); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (login_blocked()) $error = 'Troppi tentativi. Riprova tra 15 minuti.';
    elseif (login_try(trim((string)($_POST['username'] ?? '')), (string)($_POST['password'] ?? ''))) { header('Location: ' . $dest); exit; }
    else $error = 'Credenziali non valide.';
}
page_head('Accesso');
?>
<main class="wrap" style="max-width:420px">
<h1><?= $toForm ? 'Accesso operatore' : 'Accesso amministrazione' ?></h1>
<?php if ($toForm): ?><p class="hint">Per compilare le liberatorie devi essere autenticato.</p><?php endif; ?>
<?php if ($error): ?><div class="alert"><?= h($error) ?></div><?php endif; ?>
<form method="post" class="card">
  <?= csrf_field() ?>
  <?php if ($toForm): ?><input type="hidden" name="form" value="1"><input type="hidden" name="t" value="<?= h($slug) ?>"><?php endif; ?>
  <label>Utente<input name="username" autocomplete="username" required autofocus></label>
  <label style="margin-top:10px">Password<input type="password" name="password" autocomplete="current-password" required></label>
  <p><button class="btn">Entra</button></p>
</form>
</main>
<?php page_foot();
