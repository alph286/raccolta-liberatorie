<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/_nav.php';
$u = require_admin();
$st = db()->prepare('SELECT * FROM consents WHERE id = ?');
$st->execute([(int)($_GET['id'] ?? 0)]);
$c = $st->fetch();
if (!$c) { http_response_code(404); exit('Non trovata'); }
$yn = fn($b) => $b ? 'Si' : 'No';
page_head('Liberatoria ' . $c['id']);
?>
<main class="wrap">
<?php admin_nav($u); ?>
<h1>Liberatoria n. <?= (int)$c['id'] ?></h1>
<p><a class="btn" href="file.php?id=<?= (int)$c['id'] ?>&amp;t=pdf">Scarica PDF</a>
   <a class="btn secondary" href="index.php">&laquo; Elenco</a></p>
<div class="card"><dl>
<dt>Accettata il</dt><dd><?= h(date('d/m/Y H:i:s', strtotime($c['created_at']))) ?> (IP <?= h($c['ip']) ?>)</dd>
<dt>Raccolta da</dt><dd><?= h($c['operatore']) ?></dd>
<dt>Modello</dt><dd><?= h($c['template_titolo']) ?> v<?= (int)$c['template_versione'] ?></dd>
<dt>Nome</dt><dd><?= h($c['cognome'] . ' ' . $c['nome']) ?></dd>
<dt>Nato/a</dt><dd><?= h($c['luogo_nascita']) ?> il <?= h(it_date($c['data_nascita'])) ?></dd>
<dt>Codice fiscale</dt><dd><?= h($c['codice_fiscale']) ?></dd>
<dt>Indirizzo</dt><dd><?= h($c['indirizzo']) ?></dd>
<dt>Email / Tel.</dt><dd><?= h($c['email']) ?> / <?= h($c['telefono']) ?></dd>
<dt>Progetto</dt><dd><?= h($c['progetto']) ?></dd>
<dt>Riprese</dt><dd><?= h($c['luogo_riprese']) ?> <?= h(it_date($c['data_riprese'])) ?></dd>
<dt>Immagini / Audio</dt><dd><?= $yn($c['consenso_immagini']) ?> / <?= $yn($c['consenso_audio']) ?></dd>
<dt>Diffusione</dt><dd><?= $yn($c['consenso_diffusione']) ?></dd>
<dt>Privacy</dt><dd><?= $yn($c['consenso_privacy']) ?></dd>
<dt>Impronta testo</dt><dd><code><?= h($c['testo_hash']) ?></code></dd>
<dt>Firma</dt><dd><img src="file.php?id=<?= (int)$c['id'] ?>&amp;t=firma" alt="Firma" style="max-width:300px;border:1px solid #ccc"></dd>
</dl></div>
<h2>Testo accettato</h2>
<div class="card legal-text"><?= nl2br(h($c['testo_snapshot'])) ?></div>
</main>
<?php page_foot();
