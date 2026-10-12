<?php
// Copiare in config.php e compilare. config.php NON va versionato.
return [
    'db' => [
        'host' => 'localhost',
        'name' => 'liberatorie',
        'user' => 'liberatorie',
        'pass' => 'CAMBIAMI',
    ],
    'app_name'        => 'Liberatorie',
    'ente'            => 'Scuola di Nuove Tecnologie dell\'Arte - Accademia di Belle Arti di Napoli',     // titolare del trattamento, mostrato nel PDF
    'mail_from'       => 'liberatorie@TUODOMINIO.it',   // DEVE essere una casella/dominio del tuo hosting (SPF), altrimenti le mail finiscono in spam
    'mail_redazione'  => 'nuovetecnologiedellarte@gmail.com',            // riceve copia di ogni liberatoria ('' per disattivare)
    'mail_intervistato' => true,                              // invia copia PDF anche all'intervistato
    // Cartella per PDF e firme. Su hosting condiviso metterla FUORI dalla cartella pubblica (es. /home/UTENTE/liberatorie-storage).
    // Se vuota usa ./storage (protetta da .htaccess).
    'storage_dir'     => '',
    'timezone'        => 'Europe/Rome',
    'https_only'      => true,   // cookie "secure": mettere false solo per test in HTTP locale
];
