<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/consent.php';

$slug = isset($_GET['t']) ? preg_replace('/[^a-z0-9-]/', '', strtolower((string)$_GET['t'])) : null;
$tpl = find_template($slug);
$errors = []; $v = [];

if ($tpl && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $v = $_POST;
    if (!empty($_POST['sito_web'])) { http_response_code(204); exit; }   // honeypot anti-bot
    [$d, $errors] = validate_consent($_POST);
    $sig = decode_signature((string)($_POST['firma'] ?? ''));
    if (!$sig) $errors['firma'] = 'La firma e\' obbligatoria';
    if (!$errors) {
        $token = save_consent($tpl, $d, $sig[0], $sig[1]);
        unset($_SESSION['csrf']);
        header('Location: ' . base_url() . 'grazie.php?r=' . $token);
        exit;
    }
}
$val = fn($k) => h((string)($v[$k] ?? ''));
$err = fn($k) => isset($errors[$k]) ? '<span class="err">' . h($errors[$k]) . '</span>' : '';
$chk = fn($k) => !empty($v[$k]) ? ' checked' : '';

page_head($tpl ? $tpl['titolo'] : 'Liberatoria');
echo '<main class="wrap">';
if (!$tpl) { echo '<h1>Liberatoria non disponibile</h1><p>Nessun modello attivo.</p></main>'; page_foot(); exit; }
?>
<h1><?= h($tpl['titolo']) ?></h1>
<?php if ($errors): ?><div class="alert">Controlla i campi evidenziati e riprova.</div><?php endif; ?>

<form method="post" id="liberatoria" autocomplete="on" novalidate>
  <?= csrf_field() ?>
  <input type="text" name="sito_web" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">

  <section class="legal">
    <h2>Testo della liberatoria</h2>
    <div class="legal-text"><?= nl2br(h($tpl['testo'])) ?></div>
  </section>

  <fieldset>
    <legend>I tuoi dati</legend>
    <div class="grid">
      <label>Nome *<input name="nome" value="<?= $val('nome') ?>" required autocomplete="given-name"><?= $err('nome') ?></label>
      <label>Cognome *<input name="cognome" value="<?= $val('cognome') ?>" required autocomplete="family-name"><?= $err('cognome') ?></label>
      <label>Data di nascita *<input type="date" name="data_nascita" value="<?= $val('data_nascita') ?>" required><?= $err('data_nascita') ?></label>
      <label>Luogo di nascita *<input name="luogo_nascita" value="<?= $val('luogo_nascita') ?>" required><?= $err('luogo_nascita') ?></label>
      <label>Codice fiscale<input name="codice_fiscale" value="<?= $val('codice_fiscale') ?>" maxlength="16"><?= $err('codice_fiscale') ?></label>
      <label>Telefono<input type="tel" name="telefono" value="<?= $val('telefono') ?>" autocomplete="tel"></label>
      <label class="full">Indirizzo di residenza *<input name="indirizzo" value="<?= $val('indirizzo') ?>" required autocomplete="street-address"><?= $err('indirizzo') ?></label>
      <label class="full">Email * (riceverai una copia firmata)<input type="email" name="email" value="<?= $val('email') ?>" required autocomplete="email"><?= $err('email') ?></label>
    </div>
  </fieldset>

  <fieldset>
    <legend>Riprese</legend>
    <div class="grid">
      <label class="full">Progetto / intervista<input name="progetto" value="<?= $val('progetto') ?>"></label>
      <label>Luogo delle riprese<input name="luogo_riprese" value="<?= $val('luogo_riprese') ?>"></label>
      <label>Data delle riprese<input type="date" name="data_riprese" value="<?= $val('data_riprese') ?: date('Y-m-d') ?>"><?= $err('data_riprese') ?></label>
    </div>
  </fieldset>

  <fieldset>
    <legend>Consensi</legend>
    <label class="check"><input type="checkbox" name="consenso_immagini" value="1"<?= $chk('consenso_immagini') ?>> Autorizzo l'uso di fotografie e video in cui compaio</label>
    <label class="check"><input type="checkbox" name="consenso_audio" value="1"<?= $chk('consenso_audio') ?>> Autorizzo l'uso delle registrazioni audio della mia voce</label>
    <?= $err('consenso_immagini') ?>
    <label class="check"><input type="checkbox" name="consenso_diffusione" value="1"<?= $chk('consenso_diffusione') ?>> Autorizzo la diffusione e pubblicazione dei materiali (web, social, stampa, eventi)</label>
    <label class="check"><input type="checkbox" name="consenso_privacy" value="1"<?= $chk('consenso_privacy') ?>> Ho letto l'informativa privacy (art. 13 GDPR) e il testo sopra riportato *</label>
    <?= $err('consenso_privacy') ?>
    <label class="check"><input type="checkbox" name="maggiorenne" value="1"<?= $chk('maggiorenne') ?>> Dichiaro di essere maggiorenne *</label>
    <?= $err('maggiorenne') ?>
  </fieldset>

  <fieldset>
    <legend>Firma *</legend>
    <p class="hint">Firma qui sotto con il dito, la penna o il mouse.</p>
    <canvas id="firma-canvas" width="600" height="220" aria-label="Area firma"></canvas>
    <input type="hidden" name="firma" id="firma-data">
    <?= $err('firma') ?>
    <p><button type="button" id="firma-clear" class="btn secondary">Cancella firma</button></p>
  </fieldset>

  <button type="submit" class="btn">Accetto e invio</button>
</form>
</main>
<script src="<?= h(base_url()) ?>assets/form.js"></script>
<?php page_foot();
