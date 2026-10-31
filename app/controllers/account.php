<?php
declare(strict_types=1);

function my_profile(array $user): array
{
    $p = row('SELECT * FROM profiles WHERE user_id = ?', [$user['id']]);
    if (!$p) {
        // Un admin sans profil, par exemple
        q('INSERT INTO profiles (user_id, slug, first_name, email, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$user['id'], unique_slug($user['name']), $user['name'], $user['email'], now(), now()]);
        $p = row('SELECT * FROM profiles WHERE user_id = ?', [$user['id']]);
    }
    return $p;
}

function account_profile(): void
{
    $user = require_login();
    $p = my_profile($user);
    $links = rows('SELECT label FROM links WHERE profile_id = ? AND is_active = 1 ORDER BY position, id', [$p['id']]);
    view('account/profile', [
        'title' => t('Mon profil | EliteTap'), 'nav' => 'profil', 'user' => $user, 'p' => $p,
        'links' => $links, 'totals' => stats_totals((int) $p['id'], 30),
        'activeCards' => (int) scalar("SELECT COUNT(*) FROM cards WHERE profile_id = ? AND status = 'active'", [$p['id']]),
    ], 'app');
}

function account_profile_save(): void
{
    $user = require_login();
    $p = my_profile($user);

    $f = [
        'first_name' => input('first_name', 80),
        'last_name'  => input('last_name', 80),
        'title'      => input('title', 120),
        'company'    => input('company', 120),
        'bio'        => input('bio', 300),
        'phone'      => input('phone', 40),
        'email'      => strtolower(input('email', 190)),
        'website'    => input('website', 255),
        'address'    => input('address', 255),
        'slug'       => slugify(input('slug', 60)),
    ];
    $errors = [];
    if ($f['first_name'] === '' && $f['company'] === '') {
        $errors[] = t('Indiquez au moins un prénom ou une entreprise.');
    }
    if ($f['email'] !== '' && !valid_email($f['email'])) {
        $errors[] = t('Le courriel affiché n\'est pas valide.');
    }
    $web = clean_url($f['website']);
    if ($web === null) {
        $errors[] = t('L\'adresse du site web n\'est pas valide.');
    }
    if (strlen($f['slug']) < 2) {
        $errors[] = t('L\'adresse du profil doit contenir au moins 2 caractères.');
    } elseif (in_array($f['slug'], ['admin', 'compte', 'api', 'elitetap'], true)) {
        $errors[] = t('Cette adresse de profil est réservée.');
    } elseif (unique_slug($f['slug'], (int) $p['id']) !== $f['slug']) {
        $errors[] = t('Cette adresse de profil est déjà prise.');
    }

    if ($errors) {
        foreach ($errors as $e) flash('error', $e);
        with_old($f);
        redirect('/compte');
    }

    q('UPDATE profiles SET first_name=?, last_name=?, title=?, company=?, bio=?, phone=?, email=?, website=?, address=?, slug=?,
       cover_color=?, accent_color=?, is_public=?, updated_at=? WHERE id=?', [
        $f['first_name'], $f['last_name'], $f['title'], $f['company'], $f['bio'], $f['phone'], $f['email'], (string) $web,
        $f['address'], $f['slug'],
        clean_color(input('cover_color', 7), $p['cover_color']),
        clean_color(input('accent_color', 7), $p['accent_color']),
        isset($_POST['is_public']) ? 1 : 0, now(), $p['id'],
    ]);
    flash('success', t('Profil enregistré. Vos cartes affichent déjà la nouvelle version.'));
    redirect('/compte');
}

/** Photo de profil : recadrée en carré 400×400, réencodée en JPEG (supprime les métadonnées et tout contenu caché). */
function account_avatar(): void
{
    $user = require_login();
    $p = my_profile($user);
    $dir = APP_ROOT . '/public/uploads/avatars';

    if (isset($_POST['remove'])) {
        if ($p['avatar_path']) @unlink($dir . '/' . basename($p['avatar_path']));
        q('UPDATE profiles SET avatar_path = NULL, updated_at = ? WHERE id = ?', [now(), $p['id']]);
        flash('success', t('Photo retirée.'));
        redirect('/compte');
    }

    $f = $_FILES['avatar'] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 8 * 1024 * 1024) {
        flash('error', t('Choisissez une image de moins de 8 Mo.'));
        redirect('/compte');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $src = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($f['tmp_name']),
        'image/png'  => @imagecreatefrompng($f['tmp_name']),
        'image/webp' => @imagecreatefromwebp($f['tmp_name']),
        default      => false,
    };
    if (!$src) {
        flash('error', t('Formats acceptés : JPG, PNG ou WEBP.'));
        redirect('/compte');
    }
    $w = imagesx($src);
    $h = imagesy($src);
    $side = min($w, $h);
    $dst = imagecreatetruecolor(400, 400);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    imagecopyresampled($dst, $src, 0, 0, intdiv($w - $side, 2), intdiv($h - $side, 2), 400, 400, $side, $side);

    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = bin2hex(random_bytes(12)) . '.jpg';
    imagejpeg($dst, "$dir/$name", 82);
    imagedestroy($src);
    imagedestroy($dst);

    if ($p['avatar_path']) @unlink($dir . '/' . basename($p['avatar_path']));
    q('UPDATE profiles SET avatar_path = ?, updated_at = ? WHERE id = ?', [$name, now(), $p['id']]);
    flash('success', t('Photo mise à jour.'));
    redirect('/compte');
}

function account_links(): void
{
    $user = require_login();
    $p = my_profile($user);
    $links = rows('SELECT * FROM links WHERE profile_id = ? ORDER BY position, id', [$p['id']]);
    $clicks = [];
    foreach (rows("SELECT link_id, COUNT(*) n FROM events WHERE profile_id = ? AND type = 'link' GROUP BY link_id", [$p['id']]) as $r) {
        $clicks[(int) $r['link_id']] = (int) $r['n'];
    }
    view('account/links', ['title' => t('Liens | EliteTap'), 'nav' => 'liens', 'user' => $user, 'p' => $p,
        'links' => $links, 'clicks' => $clicks], 'app');
}

/** Une seule action par envoi : add | update | delete | up | down */
function account_links_save(): void
{
    $user = require_login();
    $p = my_profile($user);
    $action = input('action', 10);
    $id = (int) ($_POST['id'] ?? 0);

    $own = $id ? row('SELECT * FROM links WHERE id = ? AND profile_id = ?', [$id, $p['id']]) : null;
    if ($action !== 'add' && !$own) {
        abort(404);
    }

    if ($action === 'add' || $action === 'update') {
        $label = input('label', 80);
        $u = clean_url(input('url', 500));
        if ($label === '' || !$u) {
            flash('error', t('Indiquez un libellé et une adresse web valide (https://…).'));
            redirect('/compte/liens');
        }
        if ($action === 'add') {
            if ((int) scalar('SELECT COUNT(*) FROM links WHERE profile_id = ?', [$p['id']]) >= 20) {
                flash('error', t('Maximum 20 liens.'));
                redirect('/compte/liens');
            }
            $pos = (int) scalar('SELECT COALESCE(MAX(position), 0) + 1 FROM links WHERE profile_id = ?', [$p['id']]);
            q('INSERT INTO links (profile_id, label, url, position, is_active, created_at) VALUES (?, ?, ?, ?, 1, ?)',
                [$p['id'], $label, $u, $pos, now()]);
            flash('success', t('Lien ajouté.'));
        } else {
            q('UPDATE links SET label = ?, url = ?, is_active = ? WHERE id = ?', [$label, $u, isset($_POST['is_active']) ? 1 : 0, $id]);
            flash('success', t('Lien mis à jour.'));
        }
    } elseif ($action === 'delete') {
        q('DELETE FROM links WHERE id = ?', [$id]);
        flash('success', t('Lien supprimé.'));
    } elseif ($action === 'up' || $action === 'down') {
        $ids = array_map('intval', array_column(rows('SELECT id FROM links WHERE profile_id = ? ORDER BY position, id', [$p['id']]), 'id'));
        $i = array_search($id, $ids, true);
        $j = $action === 'up' ? $i - 1 : $i + 1;
        if ($i !== false && isset($ids[$j])) {
            [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
            foreach ($ids as $pos => $lid) {
                q('UPDATE links SET position = ? WHERE id = ?', [$pos + 1, $lid]);
            }
        }
    }
    redirect('/compte/liens');
}

function account_stats(): void
{
    $user = require_login();
    $p = my_profile($user);
    $days = in_array((int) ($_GET['jours'] ?? 30), [7, 30, 90], true) ? (int) $_GET['jours'] : 30;
    $since = date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'));
    $topLinks = rows("SELECT l.label, COUNT(*) n FROM events e JOIN links l ON l.id = e.link_id
                      WHERE e.profile_id = ? AND e.type = 'link' AND e.created_at >= ?
                      GROUP BY l.id, l.label ORDER BY n DESC LIMIT 10", [$p['id'], $since]);
    view('account/stats', ['title' => t('Statistiques | EliteTap'), 'nav' => 'stats', 'user' => $user, 'p' => $p,
        'days' => $days, 'totals' => stats_totals((int) $p['id'], $days), 'daily' => stats_daily((int) $p['id'], $days),
        'topLinks' => $topLinks], 'app');
}

function account_cards(): void
{
    $user = require_login();
    $p = my_profile($user);
    $cards = rows("SELECT c.*, (SELECT COUNT(*) FROM events e WHERE e.card_id = c.id AND e.type = 'tap') taps
                   FROM cards c WHERE c.profile_id = ? ORDER BY c.id", [$p['id']]);
    view('account/cards', ['title' => t('Mes cartes | EliteTap'), 'nav' => 'cartes', 'user' => $user, 'p' => $p, 'cards' => $cards], 'app');
}

function account_card_toggle(string $id): void
{
    $user = require_login();
    $p = my_profile($user);
    $card = row('SELECT * FROM cards WHERE id = ? AND profile_id = ?', [(int) $id, $p['id']]);
    if (!$card) {
        abort(404);
    }
    $new = $card['status'] === 'active' ? 'disabled' : 'active';
    q('UPDATE cards SET status = ? WHERE id = ?', [$new, $card['id']]);
    flash('success', $new === 'disabled'
        ? t('Carte désactivée : elle n\'ouvre plus votre profil.')
        : t('Carte réactivée.'));
    redirect('/compte/cartes');
}

function account_security(): void
{
    $user = require_login();
    view('account/security', ['title' => t('Sécurité | EliteTap'), 'nav' => 'securite', 'user' => $user, 'p' => my_profile($user)], 'app');
}

function account_security_save(): void
{
    $user = require_login();
    $hash = scalar('SELECT password_hash FROM users WHERE id = ?', [$user['id']]);
    $current = $_POST['current'] ?? '';
    $pw = $_POST['password'] ?? '';
    if (!is_string($current) || !password_verify($current, (string) $hash)) {
        flash('error', t('Mot de passe actuel incorrect.'));
    } elseif (!is_string($pw) || mb_strlen($pw) < 10) {
        flash('error', t('Le nouveau mot de passe doit contenir au moins 10 caractères.'));
    } elseif ($pw !== ($_POST['password_confirm'] ?? '')) {
        flash('error', t('Les deux mots de passe ne correspondent pas.'));
    } else {
        q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $user['id']]);
        session_regenerate_id(true);
        flash('success', t('Mot de passe modifié.'));
    }
    redirect('/compte/securite');
}
