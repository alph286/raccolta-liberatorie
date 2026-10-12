<?php
declare(strict_types=1);
// Installer: crea le tabelle, il primo admin e un modello di esempio. Si blocca da solo dopo l'uso.
// DOPO L'INSTALLAZIONE CANCELLARE QUESTO FILE.
require_once __DIR__ . '/lib/bootstrap.php';
$lock = ROOT . '/storage/installed.lock';
if (is_file($lock)) { http_response_code(403); exit('Gia\' installato. Cancella install.php dal server.'); }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $user = trim((string)($_POST['username'] ?? '')); $pass = (string)($_POST['password'] ?? '');
    if (!preg_match('/^[A-Za-z0-9_.-]{3,60}$/', $user)) $err = 'Utente: 3-60 caratteri alfanumerici.';
    elseif (strlen($pass) < 10) $err = 'La password deve avere almeno 10 caratteri.';
    else {
        $pdo = db();
        foreach (array_filter(array_map('trim', explode(';', file_get_contents(ROOT . '/schema.sql')))) as $sql) $pdo->exec($sql);
        $pdo->prepare('INSERT INTO admins (username,password_hash,created_at) VALUES (?,?,NOW())')->execute([$user, password_hash($pass, PASSWORD_DEFAULT)]);
        if ((int)$pdo->query('SELECT COUNT(*) FROM templates')->fetchColumn() === 0) {
            $testo = <<<TXT
LIBERATORIA PER L'UTILIZZO DI IMMAGINI, VIDEO E REGISTRAZIONI AUDIO

[TESTO DI ESEMPIO: farlo revisionare da un legale e sostituirlo con quello della liberatoria in uso dal menu "Modelli".]

Il/La sottoscritto/a, con la presente, AUTORIZZA a titolo gratuito l'ente titolare delle riprese a effettuare fotografie, riprese video e registrazioni audio che lo/la ritraggono, e a utilizzarle, riprodurle e diffonderle - integralmente o in parte - per finalita' didattiche, divulgative, culturali, documentaristiche e promozionali, su qualsiasi supporto e canale (sito web, social network, stampa, eventi, trasmissioni), senza limiti di tempo e di territorio.

L'autorizzazione non consente un uso lesivo della dignita', del decoro e della reputazione della persona ripresa. Resta fermo il diritto di revocare il consenso per il futuro scrivendo al titolare del trattamento.

INFORMATIVA PRIVACY (art. 13 Reg. UE 2016/679): i dati personali raccolti con questo modulo saranno trattati dal titolare esclusivamente per la gestione della presente liberatoria e per gli obblighi di legge, conservati per il tempo necessario e non ceduti a terzi, se non ai soggetti che collaborano alla realizzazione e diffusione dei materiali. L'interessato puo' esercitare i diritti di cui agli artt. 15-22 GDPR (accesso, rettifica, cancellazione, limitazione, opposizione, portabilita') contattando il titolare.
TXT;
            $pdo->prepare('INSERT INTO templates (slug,titolo,testo,versione,attiva,created_at) VALUES (?,?,?,1,1,NOW())')
                ->execute(['accademia', 'Liberatoria Accademia', $testo]);
        }
        file_put_contents($lock, date('c'));
        page_head('Installato');
        echo '<main class="wrap"><h1>Installazione completata</h1><p>Ora <strong>cancella install.php</strong> dal server e vai al <a href="admin/login.php">login admin</a>.</p></main>';
        page_foot(); exit;
    }
}
page_head('Installazione');
?>
<main class="wrap" style="max-width:480px"><h1>Installazione</h1>
<?php if ($err): ?><div class="alert"><?= h($err) ?></div><?php endif; ?>
<form method="post" class="card"><?= csrf_field() ?>
<p class="hint">Verifica che config.php sia compilato: verranno create le tabelle e il primo amministratore.</p>
<label>Utente admin<input name="username" required></label>
<label style="margin-top:10px">Password (min. 10 caratteri)<input type="password" name="password" required autocomplete="new-password"></label>
<p><button class="btn">Installa</button></p></form></main>
<?php page_foot();
