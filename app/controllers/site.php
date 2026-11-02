<?php
declare(strict_types=1);

function site_home(): void
{
    view('site/home', ['title' => t('Carte de visite NFC personnalisée au Québec | EliteTap')], 'site');
}

function site_thanks(): void
{
    $ref = $_SESSION['last_order_ref'] ?? null;
    view('site/thanks', ['title' => t('Demande reçue | EliteTap'), 'ref' => $ref], 'site');
}

function site_privacy(): void
{
    view(lang() === 'en' ? 'site/privacy.en' : 'site/privacy', ['title' => t('Politique de confidentialité | EliteTap')], 'site');
}

function site_refund(): void
{
    view(lang() === 'en' ? 'site/refund.en' : 'site/refund', ['title' => t('Politique de remboursement | EliteTap')], 'site');
}

function site_robots(): void
{
    header('Content-Type: text/plain; charset=utf-8');
    if (!is_indexable()) {
        // Préproduction : rien à indexer
        echo "User-agent: *\nDisallow: /\n";
        return;
    }
    echo "User-agent: *\n"
        . "Disallow: /admin\nDisallow: /compte\nDisallow: /connexion\nDisallow: /mot-de-passe-oublie\n"
        . "Disallow: /reinitialiser/\nDisallow: /payer/\nDisallow: /paiement/\nDisallow: /t/\nDisallow: /l/\nDisallow: /merci\n"
        . "\nSitemap: " . url('/sitemap.xml') . "\n";
}

function site_sitemap(): void
{
    header('Content-Type: application/xml; charset=utf-8');
    $pages = [
        '/'               => ['1.0', 'monthly', APP_DIR . '/views/site/home.php'],
        '/confidentialite' => ['0.3', 'yearly', APP_DIR . '/views/site/privacy.php'],
        '/remboursement'  => ['0.3', 'yearly', APP_DIR . '/views/site/refund.php'],
    ];
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
    foreach ($pages as $path => [$prio, $freq, $file]) {
        foreach (['fr' => 'fr-CA', 'en' => 'en-CA'] as $l => $_) {
            echo "  <url>\n    <loc>" . e(url($path) . ($l === 'en' ? '?lang=en' : '')) . "</loc>\n";
            echo '    <lastmod>' . date('Y-m-d', (int) filemtime($file)) . "</lastmod>\n";
            echo "    <changefreq>$freq</changefreq>\n    <priority>$prio</priority>\n";
            echo '    <xhtml:link rel="alternate" hreflang="fr-CA" href="' . e(url($path)) . "\"/>\n";
            echo '    <xhtml:link rel="alternate" hreflang="en-CA" href="' . e(url($path) . '?lang=en') . "\"/>\n";
            echo "  </url>\n";
        }
    }
    echo "</urlset>\n";
}

const UPLOAD_TYPES = [
    'image/png'       => 'png',
    'image/jpeg'      => 'jpg',
    'image/svg+xml'   => 'svg',
    'application/pdf' => 'pdf',
    'image/webp'      => 'webp',
];

function site_quote(): void
{
    // Pot de miel anti-robots : champ caché qui doit rester vide
    if (input('site_web') !== '') {
        redirect('/merci');
    }

    $data = [
        'name'      => input('name', 120),
        'company'   => input('company', 120),
        'email'     => strtolower(input('email', 190)),
        'phone'     => input('phone', 40),
        'card_type' => input('card_type', 40),
        'quantity'  => input('quantity', 6),
        'message'   => input('message', 3000),
    ];

    $errors = [];
    if ($data['name'] === '') {
        $errors[] = t('Indiquez votre nom.');
    }
    if (!valid_email($data['email'])) {
        $errors[] = t('Indiquez un courriel valide.');
    }
    if (!in_array($data['card_type'], CARD_TYPES, true)) {
        $errors[] = t('Choisissez un type de carte.');
    }
    $qty = (int) $data['quantity'];
    if ($qty < 1 || $qty > 10000) {
        $errors[] = t('La quantité doit être entre 1 et 10 000.');
    }

    $key = throttle_key('devis');
    if (too_many_attempts($key, 5, 60)) {
        $errors[] = t('Trop de demandes envoyées. Réessayez plus tard ou écrivez-nous sur WhatsApp.');
    }

    // Pièce jointe facultative
    $filePath = null;
    $fileName = null;
    $f = $_FILES['file'] ?? null;
    if ($f && ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 10 * 1024 * 1024) {
            $errors[] = t('Le fichier doit faire moins de 10 Mo.');
        } else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
            if (!isset(UPLOAD_TYPES[$mime])) {
                $errors[] = t('Formats acceptés : PNG, JPG, WEBP, SVG ou PDF.');
            } elseif (!$errors) {
                $dir = APP_ROOT . '/storage/uploads/orders/' . date('Y/m');
                if (!is_dir($dir)) {
                    mkdir($dir, 0750, true);
                }
                $stored = bin2hex(random_bytes(16)) . '.' . UPLOAD_TYPES[$mime];
                if (move_uploaded_file($f['tmp_name'], "$dir/$stored")) {
                    $filePath = date('Y/m') . '/' . $stored;
                    $fileName = mb_substr(basename((string) $f['name']), 0, 200);
                }
            }
        }
    }

    if ($errors) {
        foreach ($errors as $err) {
            flash('error', $err);
        }
        with_old($data);
        redirect('/#commande');
    }

    hit_attempt($key);

    do {
        $ref = 'ET-' . random_code(6);
    } while (scalar('SELECT id FROM orders WHERE reference = ?', [$ref]) !== null);

    q('INSERT INTO orders (reference, name, company, email, phone, card_type, quantity, message, file_path, file_name, status, lang, confirm_token, created_at, updated_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
        $ref, $data['name'], $data['company'], $data['email'], $data['phone'], $data['card_type'], $qty,
        $data['message'], $filePath, $fileName, 'devis', lang(), bin2hex(random_bytes(16)), now(), now(),
    ]);
    $orderId = (int) db()->lastInsertId();

    // Notification interne
    send_mail(
        notify_email(),
        "Nouvelle demande de devis $ref — {$data['name']}",
        "Nouvelle demande reçue sur " . public_host() . " (langue : " . strtoupper(lang()) . ")\n\n"
        . "Référence : $ref\nNom : {$data['name']}\nEntreprise : {$data['company']}\n"
        . "Courriel : {$data['email']}\nTéléphone : {$data['phone']}\n"
        . "Carte : {$data['card_type']} × $qty\n\nMessage :\n{$data['message']}\n\n"
        . ($fileName ? "Fichier joint : $fileName\n\n" : '')
        . 'Voir la commande : ' . url("/admin/commandes/$orderId") . "\n",
        $data['email']
    );

    // Courriel au client : lien pour confirmer la commande et choisir son forfait
    send_order_confirmation(row('SELECT * FROM orders WHERE id = ?', [$orderId]));

    $_SESSION['last_order_ref'] = $ref;
    redirect('/merci');
}
