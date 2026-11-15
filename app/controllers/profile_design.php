<?php
declare(strict_types=1);

/* Admin › Clients › Profil sur mesure : design de carte → gabarit HTML/CSS (IA), aperçu, versions, publication. */

function design_client(string $userId): array
{
    if (!design_ready()) {
        flash('error', 'Le profil sur mesure a besoin de la migration 008 : exécutez database/migrations/008-profil-sur-mesure.mysql.sql dans phpMyAdmin.');
        redirect('/admin/clients');
    }
    $u = row("SELECT * FROM users WHERE id = ? AND role = 'client'", [(int) $userId]) ?? abort(404);
    $p = row('SELECT * FROM profiles WHERE user_id = ?', [$u['id']]) ?? abort(404);
    return [$u, $p];
}

function design_url(array $u, ?int $designId = null): string
{
    return '/admin/clients/' . $u['id'] . '/design' . ($designId ? '?v=' . $designId : '');
}

function admin_profile_design(string $userId): void
{
    $user = require_admin();
    [$u, $p] = design_client($userId);
    $versions = rows('SELECT d.id, d.version, d.status, d.ai_used, d.source_name, d.created_at, d.updated_at, d.published_at, u.name author
                      FROM profile_designs d LEFT JOIN users u ON u.id = d.created_by WHERE d.profile_id = ? ORDER BY d.id DESC', [$p['id']]);
    $vid = (int) ($_GET['v'] ?? 0);
    $current = $vid ? row('SELECT * FROM profile_designs WHERE id = ? AND profile_id = ?', [$vid, $p['id']])
        : row('SELECT * FROM profile_designs WHERE profile_id = ? ORDER BY id DESC LIMIT 1', [$p['id']]);
    $report = [];
    $checks = [];
    if ($current && trim((string) $current['html']) !== '') {
        [, $r1] = sanitize_template((string) $current['html']);
        [, $r2] = sanitize_css((string) $current['css']);
        $report = array_merge($r1, $r2);
        $checks = design_checks((string) $current['html']);
    }
    view('admin/profile_design', ['title' => 'Profil sur mesure · ' . $u['name'] . ' | Admin EliteTap', 'nav' => 'clients', 'user' => $user,
        'client' => $u, 'p' => $p, 'versions' => $versions, 'current' => $current, 'report' => $report, 'checks' => $checks,
        'assets' => design_assets((int) $p['id']), 'aiOn' => ai_enabled()], 'admin');
}

/** Nouveau design de carte : crée une nouvelle version (brouillon) avec ce fichier. */
function admin_profile_design_source(string $userId): void
{
    $admin = require_admin();
    [$u, $p] = design_client($userId);
    $f = $_FILES['source'] ?? null;
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || $f['size'] > 15 * 1024 * 1024) {
        flash('error', 'Choisissez le fichier du design de la carte (PNG, JPG, WEBP ou PDF, 15 Mo max).');
        redirect(design_url($u));
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!isset(DESIGN_SRC_TYPES[$mime])) {
        flash('error', 'Format non accepté. PNG, JPG, WEBP ou PDF.');
        redirect(design_url($u));
    }
    $dir = DESIGN_SRC_DIR . '/' . $p['id'];
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    $file = bin2hex(random_bytes(12)) . '.' . DESIGN_SRC_TYPES[$mime];
    if (!move_uploaded_file($f['tmp_name'], "$dir/$file")) {
        flash('error', 'Le fichier n\'a pas pu être enregistré (droits du dossier storage/uploads).');
        redirect(design_url($u));
    }
    $prev = row('SELECT * FROM profile_designs WHERE profile_id = ? ORDER BY id DESC LIMIT 1', [$p['id']]);
    $version = (int) scalar('SELECT COALESCE(MAX(version), 0) + 1 FROM profile_designs WHERE profile_id = ?', [$p['id']]);
    q('INSERT INTO profile_designs (profile_id, version, source_path, source_name, source_mime, html, css, status, created_by, created_at, updated_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$p['id'], $version, $p['id'] . '/' . $file, mb_substr(basename((string) $f['name']), 0, 200), $mime,
        $prev['html'] ?? null, $prev['css'] ?? null, 'draft', $admin['id'], now(), now()]);
    $id = (int) db()->lastInsertId();
    flash('success', 'Design de la carte ajouté (version ' . $version . '). ' . (ai_enabled() ? 'Lancez la génération par l\'IA.' : 'Écrivez le gabarit, ou ajoutez la clé de l\'IA dans config.php.'));
    redirect(design_url($u, $id));
}

/** Génération ou correction par l'IA. */
function admin_profile_design_generate(string $userId, string $designId): void
{
    $admin = require_admin();
    [$u, $p] = design_client($userId);
    $d = row('SELECT * FROM profile_designs WHERE id = ? AND profile_id = ?', [(int) $designId, $p['id']]) ?? abort(404);
    if (!ai_enabled()) {
        flash('error', 'L\'IA n\'est pas configurée : ajoutez la clé API Anthropic dans config.php (anthropic › api_key).');
        redirect(design_url($u, (int) $d['id']));
    }
    $src = $d['source_path'] ? realpath(DESIGN_SRC_DIR . '/' . $d['source_path']) : null;
    if (!$src || !str_starts_with($src, realpath(DESIGN_SRC_DIR))) {
        flash('error', 'Ajoutez d\'abord le design de la carte.');
        redirect(design_url($u, (int) $d['id']));
    }
    $instruction = input('instruction', 2000);
    $revise = input('mode', 10) === 'corriger' && trim((string) $d['html']) !== '';
    try {
        $out = ai_generate_design($src, (string) $d['source_mime'], $p, design_assets((int) $p['id']), $instruction, $revise ? $d : null);
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        redirect(design_url($u, (int) $d['id']));
    }
    // Une version publiée n'est jamais modifiée : la génération crée un nouveau brouillon
    $target = design_editable_copy($d, (int) $admin['id']);
    q('UPDATE profile_designs SET html = ?, css = ?, instruction = ?, ai_used = 1, updated_at = ? WHERE id = ?',
        [$out['html'], $out['css'], $instruction ?: null, now(), $target]);
    flash('success', $revise ? 'Gabarit corrigé par l\'IA. Vérifiez l\'aperçu avant de publier.' : 'Gabarit généré par l\'IA. Vérifiez l\'aperçu avant de publier.');
    redirect(design_url($u, $target));
}

/** Brouillon modifiable : la version elle-même si c'est un brouillon, sinon une copie. */
function design_editable_copy(array $d, int $adminId): int
{
    if ($d['status'] === 'draft') {
        return (int) $d['id'];
    }
    $version = (int) scalar('SELECT COALESCE(MAX(version), 0) + 1 FROM profile_designs WHERE profile_id = ?', [$d['profile_id']]);
    q('INSERT INTO profile_designs (profile_id, version, source_path, source_name, source_mime, html, css, instruction, status, ai_used, created_by, created_at, updated_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$d['profile_id'], $version, $d['source_path'], $d['source_name'], $d['source_mime'],
        $d['html'], $d['css'], $d['instruction'], 'draft', $d['ai_used'], $adminId, now(), now()]);
    return (int) db()->lastInsertId();
}

/** Enregistrer le code, publier, archiver. */
function admin_profile_design_save(string $userId, string $designId): void
{
    $admin = require_admin();
    [$u, $p] = design_client($userId);
    $d = row('SELECT * FROM profile_designs WHERE id = ? AND profile_id = ?', [(int) $designId, $p['id']]) ?? abort(404);
    $action = input('action', 20);
    if ($action === 'save' || $action === 'save_publish') {
        $html = (string) ($_POST['html'] ?? '');
        $css = (string) ($_POST['css'] ?? '');
        if (strlen($html) > DESIGN_MAX_HTML || strlen($css) > DESIGN_MAX_CSS) {
            flash('error', 'Gabarit trop long (60 Ko max pour le HTML et pour le CSS).');
            redirect(design_url($u, (int) $d['id']));
        }
        $changed = $html !== (string) $d['html'] || $css !== (string) $d['css'];
        $target = $changed ? design_editable_copy($d, (int) $admin['id']) : (int) $d['id'];
        if ($changed) {
            q('UPDATE profile_designs SET html = ?, css = ?, updated_at = ? WHERE id = ?', [$html, $css, now(), $target]);
        }
        if ($action === 'save') {
            flash('success', $changed ? 'Brouillon enregistré.' : 'Aucun changement.');
            redirect(design_url($u, $target));
        }
        $d = row('SELECT * FROM profile_designs WHERE id = ?', [$target]);
        $action = 'publish';
    }
    if ($action === 'publish') {
        if (trim((string) $d['html']) === '') {
            flash('error', 'Le gabarit est vide.');
            redirect(design_url($u, (int) $d['id']));
        }
        q("UPDATE profile_designs SET status = 'archived' WHERE profile_id = ? AND status = 'published'", [$p['id']]);
        q("UPDATE profile_designs SET status = 'published', published_at = ?, updated_at = ? WHERE id = ?", [now(), now(), $d['id']]);
        q('UPDATE profiles SET custom_design_id = ?, use_custom = 1, updated_at = ? WHERE id = ?', [$d['id'], now(), $p['id']]);
        $lang = (string) (scalar('SELECT lang FROM users WHERE id = ?', [$u['id']]) ?: 'fr');
        if (isset($_POST['notify'])) {
            in_lang($lang, fn() => send_mail($u['email'], t('Votre profil EliteTap sur mesure est en ligne'),
                t("Bonjour {nom},\n\nVotre profil aux couleurs de votre carte est en ligne :\n{lien}\n\nVos informations se modifient toujours depuis votre espace ; le design suit automatiquement. Vous pouvez revenir au thème standard ou nous demander une modification depuis votre espace.\n\nL'équipe EliteTap\n",
                    ['nom' => $u['name'], 'lien' => url('/u/' . $p['slug'])])));
        }
        flash('success', 'Version ' . $d['version'] . ' publiée : elle s\'affiche maintenant quand on touche la carte.');
        redirect(design_url($u, (int) $d['id']));
    }
    if ($action === 'disable') {
        q('UPDATE profiles SET custom_design_id = NULL, updated_at = ? WHERE id = ?', [now(), $p['id']]);
        q("UPDATE profile_designs SET status = 'archived' WHERE profile_id = ? AND status = 'published'", [$p['id']]);
        flash('success', 'Profil sur mesure retiré : le profil standard s\'affiche de nouveau.');
        redirect(design_url($u, (int) $d['id']));
    }
    redirect(design_url($u, (int) $d['id']));
}

/** Aperçu d'une version (brouillon compris), affiché dans le cadre mobile / ordinateur. */
function admin_profile_design_preview(string $userId, string $designId): void
{
    require_admin();
    [$u, $p] = design_client($userId);
    $d = row('SELECT * FROM profile_designs WHERE id = ? AND profile_id = ?', [(int) $designId, $p['id']]) ?? abort(404);
    $links = rows('SELECT id, label, url FROM links WHERE profile_id = ? AND is_active = 1 ORDER BY position, id', [$p['id']]);
    design_csp_header();
    header('X-Robots-Tag: noindex');
    view('profile/custom', ['p' => $p, 'links' => $links, 'isOwner' => false, 'design' => $d, 'previewBar' => $d['status'] !== 'published',
        'assets' => design_assets((int) $p['id']), 'leadForm' => $p['lead_form'] && profile_can($p, 'leads'), 'leadSent' => false, 'flashes' => []]);
}

/** Fichier du design de la carte (privé). */
function admin_profile_design_source_file(string $userId, string $designId): void
{
    require_admin();
    [$u, $p] = design_client($userId);
    $d = row('SELECT * FROM profile_designs WHERE id = ? AND profile_id = ?', [(int) $designId, $p['id']]) ?? abort(404);
    $path = $d['source_path'] ? realpath(DESIGN_SRC_DIR . '/' . $d['source_path']) : false;
    if (!$path || !str_starts_with($path, realpath(DESIGN_SRC_DIR))) {
        abort(404);
    }
    header('Content-Type: ' . $d['source_mime']);
    header('Content-Length: ' . filesize($path));
    header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox");
    header('Content-Disposition: inline; filename="design-carte.' . pathinfo($path, PATHINFO_EXTENSION) . '"');
    readfile($path);
    exit;
}

/** Images d'habillage du gabarit ({{asset:nom}}) : ajout ou suppression. */
function admin_profile_design_asset(string $userId): void
{
    require_admin();
    [$u, $p] = design_client($userId);
    $back = design_url($u, (int) input('v', 12) ?: null);
    if ($del = input('delete', 40)) {
        $a = row('SELECT * FROM profile_design_assets WHERE profile_id = ? AND name = ?', [$p['id'], $del]);
        if ($a) {
            @unlink(DESIGN_ASSET_DIR . '/' . $a['path']);
            q('DELETE FROM profile_design_assets WHERE id = ?', [$a['id']]);
            flash('success', "Image {{asset:$del}} supprimée.");
        }
        redirect($back);
    }
    $name = strtolower(preg_replace('/[^a-z0-9_-]+/i', '-', input('name', 40)));
    $name = trim($name, '-');
    $f = $_FILES['asset'] ?? null;
    if ($name === '' || !$f || ($f['error'] ?? 1) !== UPLOAD_ERR_OK || $f['size'] > 4 * 1024 * 1024) {
        flash('error', 'Donnez un nom (ex. fond, motif) et choisissez une image de 4 Mo maximum.');
        redirect($back);
    }
    $types = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!isset($types[$mime])) {
        flash('error', 'Image PNG, JPG, WEBP ou GIF seulement (pas de SVG).');
        redirect($back);
    }
    $dir = DESIGN_ASSET_DIR . '/' . $p['id'];
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $file = $name . '-' . bin2hex(random_bytes(6)) . '.' . $types[$mime];
    move_uploaded_file($f['tmp_name'], "$dir/$file");
    $old = row('SELECT * FROM profile_design_assets WHERE profile_id = ? AND name = ?', [$p['id'], $name]);
    if ($old) {
        @unlink(DESIGN_ASSET_DIR . '/' . $old['path']);
        q('UPDATE profile_design_assets SET path = ?, created_at = ? WHERE id = ?', [$p['id'] . '/' . $file, now(), $old['id']]);
    } else {
        q('INSERT INTO profile_design_assets (profile_id, name, path, created_at) VALUES (?, ?, ?, ?)', [$p['id'], $name, $p['id'] . '/' . $file, now()]);
    }
    flash('success', "Image ajoutée : utilisez {{asset:$name}} dans le gabarit.");
    redirect($back);
}

/* ------------------------------------------------------------------ */
/* Côté client                                                         */
/* ------------------------------------------------------------------ */

function account_design_toggle(): void
{
    $user = require_login();
    $p = my_profile($user);
    if (!empty($p['custom_design_id'])) {
        $on = input('use_custom', 1) === '1';
        q('UPDATE profiles SET use_custom = ?, updated_at = ? WHERE id = ?', [$on ? 1 : 0, now(), $p['id']]);
        flash('success', $on ? t('Votre profil sur mesure est de nouveau affiché.') : t('Votre profil affiche maintenant le thème standard. Vous pouvez revenir au design sur mesure à tout moment.'));
    }
    redirect('/compte');
}

function account_design_request(): void
{
    $user = require_login();
    $p = my_profile($user);
    $msg = trim(input('message', 2000));
    if ($msg === '') {
        flash('error', t('Décrivez la modification souhaitée.'));
        redirect('/compte#sur-mesure');
    }
    $key = throttle_key('design_request', (string) $user['id']);
    if (too_many_attempts($key, 5, 1440)) {
        flash('error', t('Trop de demandes aujourd\'hui. Écrivez-nous à info@elitetap.ca.'));
        redirect('/compte#sur-mesure');
    }
    hit_attempt($key);
    if (inbox_ready()) {
        $tid = thread_create($user['email'], $user['name'], 'Profil sur mesure : demande de modification', null, $user['lang'] ?? 'fr');
        thread_add_message($tid, ['direction' => 'in', 'kind' => 'form', 'from_email' => $user['email'], 'from_name' => $user['name'],
            'subject' => 'Profil sur mesure : demande de modification',
            'body' => $msg . "\n\nProfil : " . url('/u/' . $p['slug']) . "\nModifier le design : " . url('/admin/clients/' . $user['id'] . '/design')]);
    }
    send_mail(notify_email(), 'Profil sur mesure : demande de ' . $user['name'],
        $msg . "\n\nProfil : " . url('/u/' . $p['slug']) . "\nModifier le design : " . url('/admin/clients/' . $user['id'] . '/design') . "\n", $user['email']);
    flash('success', t('Demande envoyée. Notre équipe vous répond rapidement.'));
    redirect('/compte#sur-mesure');
}
