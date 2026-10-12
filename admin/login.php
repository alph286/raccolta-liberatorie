<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
if (admin_user()) { header('Location: index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (login_blocked()) $error = 'Troppi tentativi. Riprova tra 15 minuti.';
    elseif (login_try(trim((string)($_POST['username'] ?? '')), (string)($_POST['password'] ?? ''))) { header('Location: index.php'); exit; }
    else $error = 'Credenziali non valide.';
}
page_head('Accesso');
?>
<main class="wrap" style="max-width:420px">
<h1>Accesso amministrazione</h1>
<?php if ($error): ?><div class="alert"><?= h($error) ?></div><?php endif; ?>
<form method="post" class="card">
  <?= csrf_field() ?>
  <label>Utente<input name="username" autocomplete="username" required autofocus></label>
  <label style="margin-top:10px">Password<input type="password" name="password" autocomplete="current-password" required></label>
  <p><button class="btn">Entra</button></p>
</form>
</main>
<?php page_foot();
