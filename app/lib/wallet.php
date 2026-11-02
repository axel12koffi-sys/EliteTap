<?php
declare(strict_types=1);

/*
 * Ajout au Wallet (forfait Pro).
 *  - Apple Wallet : fichier .pkpass signé avec le certificat « Pass Type ID » d'un compte Apple Developer.
 *  - Google Wallet : lien « Enregistrer dans Google Wallet » (JWT signé avec un compte de service Google Cloud).
 * Chaque bouton n'apparaît que si sa configuration est présente dans config.php (clé 'wallet').
 */

function wallet_apple_enabled(): bool
{
    $a = config('wallet', [])['apple'] ?? [];
    return !empty($a['pass_type_id']) && !empty($a['team_id']) && !empty($a['p12_path'])
        && is_readable((string) $a['p12_path']) && !empty($a['wwdr_path']) && is_readable((string) $a['wwdr_path']);
}

function wallet_google_enabled(): bool
{
    $g = config('wallet', [])['google'] ?? [];
    return !empty($g['issuer_id']) && !empty($g['service_account_json']) && is_readable((string) $g['service_account_json']);
}

/** Couleur hexadécimale → « rgb(r, g, b) » (format Apple). */
function hex_to_rgb(string $hex): string
{
    $hex = ltrim($hex, '#');
    return sprintf('rgb(%d, %d, %d)', hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)));
}

/** Image PNG carrée ou rectangulaire, transparence conservée, à partir d'un fichier source. */
function wallet_png(string $src, int $w, int $h, bool $crop = false): ?string
{
    if (!is_file($src)) {
        return null;
    }
    $data = file_get_contents($src);
    $img = @imagecreatefromstring((string) $data);
    if (!$img) {
        return null;
    }
    $sw = imagesx($img);
    $sh = imagesy($img);
    if ($crop) {
        $side = min($sw, $sh);
        $sx = intdiv($sw - $side, 2);
        $sy = intdiv($sh - $side, 2);
        $sw = $sh = $side;
        $dw = $w;
        $dh = $h;
    } else {
        $sx = $sy = 0;
        $scale = min($w / $sw, $h / $sh);
        $dw = max(1, (int) round($sw * $scale));
        $dh = max(1, (int) round($sh * $scale));
    }
    $dst = imagecreatetruecolor($dw, $dh);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
    imagecopyresampled($dst, $img, 0, 0, $sx, $sy, $dw, $dh, $sw, $sh);
    ob_start();
    imagepng($dst);
    $out = (string) ob_get_clean();
    imagedestroy($img);
    imagedestroy($dst);
    return $out;
}

/** Contenu du pass.json pour un profil (type « generic »). */
function apple_pass_json(array $p): array
{
    $a = config('wallet')['apple'];
    [$cover] = profile_colors($p);
    $fg = on_color($cover);
    $name = full_name($p) ?: $p['company'];
    $profileUrl = url('/u/' . $p['slug']);
    $secondary = [];
    if ($p['title'] !== '') $secondary[] = ['key' => 'title', 'label' => t('Titre'), 'value' => $p['title']];
    if ($p['company'] !== '' && $p['company'] !== $name) $secondary[] = ['key' => 'company', 'label' => t('Entreprise'), 'value' => $p['company']];
    $aux = [];
    if ($p['phone'] !== '') $aux[] = ['key' => 'phone', 'label' => t('Téléphone'), 'value' => $p['phone']];
    if ($p['email'] !== '') $aux[] = ['key' => 'email', 'label' => t('Courriel'), 'value' => $p['email']];
    $back = [['key' => 'profile', 'label' => t('Profil EliteTap'), 'value' => $profileUrl]];
    if ($p['website'] !== '') $back[] = ['key' => 'web', 'label' => t('Site web'), 'value' => $p['website']];
    if ($p['bio'] !== '') $back[] = ['key' => 'bio', 'label' => t('Présentation'), 'value' => $p['bio']];

    return [
        'formatVersion'      => 1,
        'passTypeIdentifier' => $a['pass_type_id'],
        'teamIdentifier'     => $a['team_id'],
        'serialNumber'       => 'elitetap-' . $p['id'],
        'organizationName'   => 'EliteTap',
        'description'        => t('Carte de visite de {nom}', ['nom' => $name]),
        'logoText'           => $p['company'] !== '' ? $p['company'] : 'EliteTap',
        'backgroundColor'    => hex_to_rgb($cover),
        'foregroundColor'    => hex_to_rgb($fg),
        'labelColor'         => hex_to_rgb($fg),
        'sharingProhibited'  => false,
        'generic' => [
            'primaryFields'   => [['key' => 'name', 'label' => '', 'value' => $name]],
            'secondaryFields' => $secondary,
            'auxiliaryFields' => $aux,
            'backFields'      => $back,
        ],
        'barcodes' => [[
            'format' => 'PKBarcodeFormatQR', 'message' => $profileUrl, 'messageEncoding' => 'iso-8859-1',
            'altText' => t('Scannez pour ouvrir mon profil'),
        ]],
    ];
}

/** Construit le fichier .pkpass (zip signé). Renvoie le contenu binaire. */
function apple_pkpass(array $p): string
{
    $a = config('wallet')['apple'];
    [$cover] = profile_colors($p);
    $darkBg = on_color($cover) === '#FFFFFF';
    $logoSrc = $p['logo_path'] && profile_can($p, 'brand')
        ? APP_ROOT . '/public/uploads/avatars/' . basename($p['logo_path'])
        : APP_ROOT . '/public/assets/img/' . ($darkBg ? 'logo-white.png' : 'logo-color.png');

    $files = [
        'pass.json'      => json_encode(apple_pass_json($p), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'icon.png'       => wallet_png(APP_ROOT . '/public/assets/img/favicon.png', 29, 29),
        'icon@2x.png'    => wallet_png(APP_ROOT . '/public/assets/img/favicon.png', 58, 58),
        'icon@3x.png'    => wallet_png(APP_ROOT . '/public/assets/img/favicon.png', 87, 87),
        'logo.png'       => wallet_png($logoSrc, 160, 50),
        'logo@2x.png'    => wallet_png($logoSrc, 320, 100),
    ];
    if ($p['avatar_path']) {
        $av = APP_ROOT . '/public/uploads/avatars/' . basename($p['avatar_path']);
        $files['thumbnail.png'] = wallet_png($av, 90, 90, true);
        $files['thumbnail@2x.png'] = wallet_png($av, 180, 180, true);
    }
    $files = array_filter($files, fn($c) => $c !== null && $c !== '');

    $manifest = [];
    foreach ($files as $name => $content) {
        $manifest[$name] = sha1($content);
    }
    $manifestJson = json_encode($manifest, JSON_UNESCAPED_SLASHES);
    $files['manifest.json'] = $manifestJson;
    $files['signature'] = apple_sign_manifest($manifestJson, $a);

    $tmp = tempnam(sys_get_temp_dir(), 'pkpass');
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Impossible de créer le fichier .pkpass');
    }
    foreach ($files as $name => $content) {
        $zip->addFromString($name, $content);
    }
    $zip->close();
    $out = (string) file_get_contents($tmp);
    @unlink($tmp);
    return $out;
}

/** Signature PKCS#7 détachée (DER) du manifest, avec le certificat Apple et l'intermédiaire WWDR. */
function apple_sign_manifest(string $manifest, array $a): string
{
    $certs = [];
    if (!openssl_pkcs12_read((string) file_get_contents($a['p12_path']), $certs, (string) ($a['p12_password'] ?? ''))) {
        throw new RuntimeException('Certificat Apple (.p12) illisible : vérifier le chemin et le mot de passe');
    }
    $dir = sys_get_temp_dir();
    $in = tempnam($dir, 'mf');
    $out = tempnam($dir, 'sg');
    file_put_contents($in, $manifest);
    $ok = openssl_pkcs7_sign($in, $out, $certs['cert'], $certs['pkey'], [], PKCS7_BINARY | PKCS7_DETACHED, $a['wwdr_path']);
    $smime = (string) file_get_contents($out);
    @unlink($in);
    @unlink($out);
    if (!$ok) {
        throw new RuntimeException('Signature du pass impossible : ' . openssl_error_string());
    }
    // Le résultat est un message S/MIME : on extrait la partie signature (base64) et on la décode en DER
    if (!preg_match('/filename="?smime\.p7s"?\s*\R\R(.+?)\R\R?------/s', $smime, $m)) {
        throw new RuntimeException('Format de signature inattendu');
    }
    return (string) base64_decode(preg_replace('/\s+/', '', $m[1]));
}

function base64url(string $s): string
{
    return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
}

/** Lien « Enregistrer dans Google Wallet » (pass générique créé à la volée par le JWT). */
function google_wallet_url(array $p): string
{
    $g = config('wallet')['google'];
    $sa = json_decode((string) file_get_contents($g['service_account_json']), true);
    if (empty($sa['client_email']) || empty($sa['private_key'])) {
        throw new RuntimeException('Fichier du compte de service Google invalide');
    }
    [$cover] = profile_colors($p);
    $name = full_name($p) ?: $p['company'];
    $profileUrl = url('/u/' . $p['slug']);
    $issuer = (string) $g['issuer_id'];
    $classId = $issuer . '.elitetap_profile';
    $logo = $p['logo_path'] && profile_can($p, 'brand')
        ? url('/uploads/avatars/' . $p['logo_path'])
        : url('/assets/img/favicon.png');
    $lang = lang() === 'en' ? 'en-CA' : 'fr-CA';
    $loc = fn(string $v) => ['defaultValue' => ['language' => $lang, 'value' => $v]];

    $object = [
        'id'      => $issuer . '.profile_' . $p['id'],
        'classId' => $classId,
        'state'   => 'ACTIVE',
        'cardTitle' => $loc($p['company'] !== '' ? $p['company'] : 'EliteTap'),
        'header'    => $loc($name),
        'subheader' => $loc($p['title'] !== '' ? $p['title'] : t('Carte de visite')),
        'logo'      => ['sourceUri' => ['uri' => $logo]],
        'hexBackgroundColor' => $cover,
        'barcode'   => ['type' => 'QR_CODE', 'value' => $profileUrl, 'alternateText' => t('Scannez pour ouvrir mon profil')],
        'linksModuleData' => ['uris' => array_values(array_filter([
            ['uri' => $profileUrl, 'description' => t('Profil EliteTap')],
            $p['website'] !== '' ? ['uri' => $p['website'], 'description' => t('Site web')] : null,
            $p['phone'] !== '' ? ['uri' => 'tel:' . preg_replace('/[^0-9+]/', '', $p['phone']), 'description' => $p['phone']] : null,
            $p['email'] !== '' ? ['uri' => 'mailto:' . $p['email'], 'description' => $p['email']] : null,
        ]))],
    ];
    $claims = [
        'iss' => $sa['client_email'],
        'aud' => 'google',
        'typ' => 'savetowallet',
        'iat' => time(),
        'origins' => [rtrim((string) config('app_url'), '/')],
        'payload' => [
            'genericClasses' => [['id' => $classId]],
            'genericObjects' => [$object],
        ],
    ];
    $head = base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $body = base64url(json_encode($claims, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    if (!openssl_sign("$head.$body", $sig, $sa['private_key'], OPENSSL_ALGO_SHA256)) {
        throw new RuntimeException('Signature Google Wallet impossible');
    }
    return 'https://pay.google.com/gp/v/save/' . "$head.$body." . base64url($sig);
}
