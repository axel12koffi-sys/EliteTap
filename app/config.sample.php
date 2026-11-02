<?php
// Copier en app/config.php et remplir. Ne jamais versionner config.php.
return [
    'app_url'   => 'https://elitetap.ca',      // sans barre finale
    'debug'     => false,
    // Indexation Google : automatique (oui sur elitetap.ca, non ailleurs). Forcer avec true/false si besoin.
    // 'indexable' => false,

    // Forfaits : true = fonctions Pro réservées aux forfaits Pro / Entreprise (plan d'évolution).
    // false = toutes les fonctions ouvertes à tous les clients.
    'plans_enforced' => true,

    // Prospects conservés N jours, puis supprimés automatiquement (Loi 25)
    'lead_retention_days' => 730,

    'db' => [
        'dsn'  => 'mysql:host=localhost;dbname=CPANELUSER_elitetap;charset=utf8mb4',
        'user' => 'CPANELUSER_elitetap',
        'pass' => 'MOT_DE_PASSE',
    ],

    'mail' => [
        'from'       => 'info@elitetap.ca',
        'from_name'  => 'EliteTap',
        'notify_to'  => 'info@elitetap.ca',      // notifications internes (modifiable dans Admin › Réglages)
        // Envoi fiable par SMTP (recommandé). Laisser commenté pour utiliser mail() du serveur.
        // Créer d'abord la boîte info@elitetap.ca dans cPanel › Comptes de messagerie.
        // 'smtp' => [
        //     'host'   => 'mail.elitetap.ca',
        //     'port'   => 465,
        //     'secure' => 'ssl',        // 'ssl' (465) ou 'tls' (587)
        //     'user'   => 'info@elitetap.ca',
        //     'pass'   => 'MOT_DE_PASSE',
        // ],
    ],

    // Ajout au Wallet (facultatif, forfait Pro) : voir le README, section « Wallet ».
    // 'wallet' => [
    //     'apple' => [
    //         'pass_type_id'  => 'pass.ca.elitetap.card',
    //         'team_id'       => 'ABCDE12345',
    //         'p12_path'      => '/home/UTILISATEUR/wallet/pass.p12',
    //         'p12_password'  => '',
    //         'wwdr_path'     => '/home/UTILISATEUR/wallet/AppleWWDRCAG4.pem',
    //     ],
    //     'google' => [
    //         'issuer_id'            => '3388000000000000000',
    //         'service_account_json' => '/home/UTILISATEUR/wallet/google-service-account.json',
    //     ],
    // ],

    // Paiement en ligne (facultatif). Laisser vide pour désactiver.
    // Stripe > Développeurs > Clés API ; webhook vers https://VOTRE-DOMAINE/stripe/webhook
    // (événements : checkout.session.completed, checkout.session.async_payment_succeeded,
    //  customer.subscription.updated, customer.subscription.deleted)
    'stripe' => [
        'secret_key'     => '',   // sk_test_… pour tester, sk_live_… en production
        'webhook_secret' => '',   // whsec_…
        // Abonnement Pro : créer le produit « EliteTap Pro » dans Stripe (Catalogue de produits)
        // avec un prix mensuel et/ou annuel, puis coller ici leurs identifiants (price_…).
        'price_pro_monthly' => '',
        'price_pro_yearly'  => '',
    ],

    'whatsapp'  => '14382207167',
    // Chaîne aléatoire longue (ex. : php -r "echo bin2hex(random_bytes(32));")
    // Sert à anonymiser les visiteurs dans les statistiques.
    'secret'    => 'CHANGER_MOI',
];
