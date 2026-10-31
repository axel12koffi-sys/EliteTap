<?php
declare(strict_types=1);

require APP_DIR . '/controllers/site.php';
require APP_DIR . '/controllers/profile.php';
require APP_DIR . '/controllers/auth.php';
require APP_DIR . '/controllers/account.php';
require APP_DIR . '/controllers/admin.php';
require APP_DIR . '/controllers/payment.php';

const ROUTES = [
    // Site public
    ['GET',  '/',                              'site_home'],
    ['POST', '/devis',                         'site_quote'],
    ['GET',  '/merci',                         'site_thanks'],
    ['GET',  '/confidentialite',               'site_privacy'],
    ['GET',  '/remboursement',                 'site_refund'],
    ['GET',  '/robots\.txt',                  'site_robots'],
    ['GET',  '/sitemap\.xml',                 'site_sitemap'],

    // Cartes et profils
    ['GET',  '/t/([A-Z0-9]{6,16})',            'card_tap'],
    ['POST', '/t/([A-Z0-9]{6,16})/activer',    'card_activate'],
    ['GET',  '/u/([a-z0-9-]{2,60})',           'profile_show'],
    ['GET',  '/u/([a-z0-9-]{2,60})/contact\.vcf', 'profile_vcard'],
    ['GET',  '/l/(\d+)',                       'link_go'],

    // Paiement en ligne
    ['GET',  '/payer/(ET-[A-Z0-9]{6})/([a-f0-9]{32})', 'pay_show'],
    ['POST', '/payer/(ET-[A-Z0-9]{6})/([a-f0-9]{32})', 'pay_start'],
    ['GET',  '/paiement/merci',                'pay_thanks'],
    ['POST', '/stripe/webhook',                'stripe_webhook'],

    // Authentification
    ['GET',  '/connexion',                     'auth_login_form'],
    ['POST', '/connexion',                     'auth_login'],
    ['POST', '/deconnexion',                   'auth_logout'],
    ['GET',  '/mot-de-passe-oublie',           'auth_forgot_form'],
    ['POST', '/mot-de-passe-oublie',           'auth_forgot'],
    ['GET',  '/reinitialiser/([a-f0-9]{64})',  'auth_reset_form'],
    ['POST', '/reinitialiser/([a-f0-9]{64})',  'auth_reset'],

    // Espace client
    ['GET',  '/compte',                        'account_profile'],
    ['POST', '/compte',                        'account_profile_save'],
    ['POST', '/compte/photo',                  'account_avatar'],
    ['GET',  '/compte/liens',                  'account_links'],
    ['POST', '/compte/liens',                  'account_links_save'],
    ['GET',  '/compte/statistiques',           'account_stats'],
    ['GET',  '/compte/cartes',                 'account_cards'],
    ['POST', '/compte/cartes/(\d+)',           'account_card_toggle'],
    ['GET',  '/compte/securite',               'account_security'],
    ['POST', '/compte/securite',               'account_security_save'],

    // Administration
    ['GET',  '/admin',                         'admin_orders'],
    ['GET',  '/admin/commandes/(\d+)',         'admin_order'],
    ['POST', '/admin/commandes/(\d+)',         'admin_order_save'],
    ['POST', '/admin/commandes/(\d+)/client',  'admin_order_client'],
    ['GET',  '/admin/commandes/(\d+)/fichier', 'admin_order_file'],
    ['POST', '/admin/commandes/(\d+)/paiement', 'admin_order_payment'],
    ['GET',  '/admin/cartes',                  'admin_cards'],
    ['POST', '/admin/cartes',                  'admin_cards_generate'],
    ['POST', '/admin/cartes/(\d+)',            'admin_card_save'],
    ['GET',  '/admin/cartes/export\.csv',      'admin_cards_export'],
    ['GET',  '/admin/cartes/qr',               'admin_cards_qr'],
    ['GET',  '/admin/clients',                 'admin_clients'],
    ['POST', '/admin/clients',                 'admin_client_create'],
    ['POST', '/admin/clients/(\d+)/lien',      'admin_client_reset_link'],
];

function dispatch(string $method, string $path): void
{
    $allowed = false;
    foreach (ROUTES as [$m, $pattern, $handler]) {
        if (!preg_match('#^' . $pattern . '$#', $path, $matches)) {
            continue;
        }
        $allowed = true;
        if ($m !== $method && !($m === 'GET' && $method === 'HEAD')) {
            continue;
        }
        // Le webhook Stripe est authentifié par sa signature, pas par un jeton CSRF
        if ($method === 'POST' && $handler !== 'stripe_webhook') {
            csrf_check();
        }
        array_shift($matches);
        $handler(...$matches);
        return;
    }
    if ($allowed) {
        http_response_code(405);
        exit('Méthode non permise.');
    }
    abort(404);
}
