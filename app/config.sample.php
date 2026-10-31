<?php
// Copier en app/config.php et remplir. Ne jamais versionner config.php.
return [
    'app_url'   => 'https://elitetap.ca',      // sans barre finale
    'debug'     => false,

    'db' => [
        'dsn'  => 'mysql:host=localhost;dbname=CPANELUSER_elitetap;charset=utf8mb4',
        'user' => 'CPANELUSER_elitetap',
        'pass' => 'MOT_DE_PASSE',
    ],

    'mail' => [
        'from'       => 'info@elitetap.ca',
        'from_name'  => 'EliteTap',
        'notify_to'  => 'info@elitetap.ca',      // reçoit les nouvelles demandes de devis
    ],

    'whatsapp'  => '14382207167',
    // Chaîne aléatoire longue (ex. : php -r "echo bin2hex(random_bytes(32));")
    // Sert à anonymiser les visiteurs dans les statistiques.
    'secret'    => 'CHANGER_MOI',
];
