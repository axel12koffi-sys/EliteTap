<?php
declare(strict_types=1);

function site_home(): void
{
    view('site/home', ['title' => 'Carte de visite NFC personnalisée au Québec | EliteTap'], 'site');
}

function site_thanks(): void
{
    $ref = $_SESSION['last_order_ref'] ?? null;
    view('site/thanks', ['title' => 'Demande reçue | EliteTap', 'ref' => $ref], 'site');
}

function site_privacy(): void
{
    view('site/privacy', ['title' => 'Politique de confidentialité | EliteTap'], 'site');
}

function site_refund(): void
{
    view('site/refund', ['title' => 'Politique de remboursement | EliteTap'], 'site');
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
        $errors[] = 'Indiquez votre nom.';
    }
    if (!valid_email($data['email'])) {
        $errors[] = 'Indiquez un courriel valide.';
    }
    if (!in_array($data['card_type'], CARD_TYPES, true)) {
        $errors[] = 'Choisissez un type de carte.';
    }
    $qty = (int) $data['quantity'];
    if ($qty < 1 || $qty > 10000) {
        $errors[] = 'La quantité doit être entre 1 et 10 000.';
    }

    $key = throttle_key('devis');
    if (too_many_attempts($key, 5, 60)) {
        $errors[] = 'Trop de demandes envoyées. Réessayez plus tard ou écrivez-nous sur WhatsApp.';
    }

    // Pièce jointe facultative
    $filePath = null;
    $fileName = null;
    $f = $_FILES['file'] ?? null;
    if ($f && ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 10 * 1024 * 1024) {
            $errors[] = 'Le fichier doit faire moins de 10 Mo.';
        } else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
            if (!isset(UPLOAD_TYPES[$mime])) {
                $errors[] = 'Formats acceptés : PNG, JPG, WEBP, SVG ou PDF.';
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

    q('INSERT INTO orders (reference, name, company, email, phone, card_type, quantity, message, file_path, file_name, status, created_at, updated_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
        $ref, $data['name'], $data['company'], $data['email'], $data['phone'], $data['card_type'], $qty,
        $data['message'], $filePath, $fileName, 'devis', now(), now(),
    ]);
    $orderId = (int) db()->lastInsertId();

    // Notification interne
    send_mail(
        config('mail')['notify_to'],
        "Nouvelle demande de devis $ref — {$data['name']}",
        "Nouvelle demande reçue sur elitetap.ca\n\n"
        . "Référence : $ref\nNom : {$data['name']}\nEntreprise : {$data['company']}\n"
        . "Courriel : {$data['email']}\nTéléphone : {$data['phone']}\n"
        . "Carte : {$data['card_type']} × $qty\n\nMessage :\n{$data['message']}\n\n"
        . ($fileName ? "Fichier joint : $fileName\n\n" : '')
        . 'Voir la commande : ' . url("/admin/commandes/$orderId") . "\n",
        $data['email']
    );

    // Accusé de réception au client
    send_mail(
        $data['email'],
        "Votre demande EliteTap ($ref)",
        "Bonjour {$data['name']},\n\n"
        . "Merci pour votre demande de carte {$data['card_type']}. Nous vous répondons en moins de 24 h avec votre devis.\n\n"
        . "Référence : $ref\n\n"
        . "Une question d'ici là ? Répondez à ce courriel ou écrivez-nous sur WhatsApp : https://wa.me/" . config('whatsapp') . "\n\n"
        . "L'équipe EliteTap\n" . url('/') . "\n"
    );

    $_SESSION['last_order_ref'] = $ref;
    redirect('/merci');
}
