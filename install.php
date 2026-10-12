<?php
declare(strict_types=1);
// Installer: crea le tabelle, il primo admin e un modello di esempio. Si blocca da solo dopo l'uso.
// DOPO L'INSTALLAZIONE CANCELLARE QUESTO FILE.
require_once __DIR__ . '/lib/bootstrap.php';
$lock = ROOT . '/installed.lock';
$sdir = storage_path();
foreach ([$sdir, $sdir . '/pdf', $sdir . '/firme'] as $d) { if (!is_dir($d)) @mkdir($d, 0750, true); }
if (!is_writable($sdir . '/pdf') || !is_writable($sdir . '/firme')) { http_response_code(500); exit('Cartella storage non scrivibile: ' . h($sdir)); }
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
            $testo = <<<'TXT'
LIBERATORIA PER L'UTILIZZO DI IMMAGINI, VIDEO E REGISTRAZIONI AUDIO

Il/La sottoscritto/a, i cui dati anagrafici sono indicati nel presente modulo, dichiarando di essere maggiorenne e nel pieno possesso delle proprie capacità,

PREMESSO CHE

la Scuola di Nuove Tecnologie dell'Arte - Accademia di Belle Arti di Napoli (di seguito "il Titolare"), con sede in Via S. Maria di Costantinopoli, 107, 80138 Napoli (NA), realizza riprese fotografiche, video e registrazioni audio nell'ambito delle proprie attività di ricerca, didattica, divulgazione e documentazione, e che il/la sottoscritto/a è stato/a ripreso/a o intervistato/a nel progetto indicato nel presente modulo;

AUTORIZZA

1. Oggetto. Il Titolare a realizzare, conservare ed elaborare (anche mediante montaggio, ritaglio, sottotitolazione e trascrizione) fotografie, riprese video e registrazioni audio che ritraggono il/la sottoscritto/a o ne riproducono la voce, nonché le dichiarazioni rese durante l'intervista (di seguito "i Materiali"), nei limiti dei consensi specifici indicati nel modulo.

2. Utilizzo. L'utilizzo e la diffusione dei Materiali, integralmente o in parte, per finalità didattiche, scientifiche, culturali, documentaristiche, informative e promozionali del Titolare, su qualsiasi supporto e canale: sito web, piattaforme video e social network, stampa, pubblicazioni, mostre, eventi, proiezioni e trasmissioni.

3. Durata e territorio. L'autorizzazione non ha limiti di territorio e ha durata di 10 (dieci) anni dalla data di firma, salvo revoca ai sensi del punto 6.

4. Gratuità. L'autorizzazione è prestata a titolo gratuito. Il/La sottoscritto/a dichiara di non avere nulla a pretendere, ora o in futuro, per l'utilizzo dei Materiali secondo quanto qui previsto.

5. Tutela della dignità. L'utilizzo dei Materiali non deve avvenire in contesti lesivi della dignità, del decoro o della reputazione del/della sottoscritto/a, ai sensi dell'art. 10 c.c. e degli artt. 96 e 97 L. 633/1941. Il Titolare non cede i Materiali a terzi per usi commerciali diversi da quelli qui indicati.

6. Revoca. Il/La sottoscritto/a può revocare in qualsiasi momento il consenso per il futuro scrivendo a nuovetecnologiedellarte@gmail.com. La revoca non pregiudica l'utilizzo già avvenuto né le copie già diffuse prima della revoca; il Titolare si impegna a non utilizzare i Materiali in nuove pubblicazioni e a rimuoverli, entro tempi ragionevoli, dai canali che controlla.

7. Consensi separati. Il consenso all'uso di immagini/video, quello all'uso dell'audio e quello alla diffusione sono distinti e facoltativi tra loro. Resta fermo che senza almeno uno dei primi due non è possibile registrare la liberatoria.

INFORMATIVA SUL TRATTAMENTO DEI DATI PERSONALI (art. 13 Reg. UE 2016/679)

- Titolare del trattamento: Scuola di Nuove Tecnologie dell'Arte - Accademia di Belle Arti di Napoli, Via S. Maria di Costantinopoli, 107, 80138 Napoli (NA), nuovetecnologiedellarte@gmail.com.
- Dati trattati: dati anagrafici e di contatto forniti nel modulo, firma, indirizzo IP e data/ora di sottoscrizione, immagini, video e voce contenuti nei Materiali.
- Finalità e base giuridica: (a) gestione e conservazione della presente liberatoria e dimostrazione del consenso prestato (obbligo di accountability, art. 6.1.c e 6.1.f GDPR); (b) utilizzo e diffusione dei Materiali secondo quanto autorizzato (consenso, art. 6.1.a GDPR).
- Conservazione: i dati della liberatoria sono conservati per la durata dell'autorizzazione e, successivamente, per il tempo necessario a tutelare i diritti del Titolare (di regola 10 anni); i Materiali pubblicati restano disponibili fino a revoca o cessazione dell'autorizzazione.
- Destinatari: personale autorizzato del Titolare e fornitori di servizi tecnici (hosting, posta elettronica) nominati responsabili del trattamento. I Materiali pubblicati online possono essere visibili a chiunque; i dati anagrafici non vengono diffusi.
- Trasferimenti extra UE: possibili solo in caso di pubblicazione su piattaforme di terzi (es. social network), secondo le garanzie previste dal GDPR.
- Diritti: accesso, rettifica, cancellazione, limitazione, opposizione, portabilità (artt. 15-22 GDPR) e revoca del consenso, scrivendo a nuovetecnologiedellarte@gmail.com. È possibile proporre reclamo al Garante per la protezione dei dati personali (www.garanteprivacy.it).
- Il conferimento dei dati è facoltativo, ma senza di essi non è possibile procedere con le riprese e la loro pubblicazione.
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
