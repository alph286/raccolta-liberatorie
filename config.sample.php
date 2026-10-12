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
    'ente'            => 'Nuove Tecnologie dell\'Arte',     // titolare del trattamento, mostrato nel PDF
    'mail_from'       => 'liberatorie@example.it',            // mittente delle email
    'mail_redazione'  => 'redazione@example.it',              // riceve copia di ogni liberatoria ('' per disattivare)
    'mail_intervistato' => true,                              // invia copia PDF anche all'intervistato
    'timezone'        => 'Europe/Rome',
    'https_only'      => true,   // cookie "secure": mettere false solo per test in HTTP locale
];
