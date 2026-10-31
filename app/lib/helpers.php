<?php
declare(strict_types=1);

function config(string $key, mixed $default = null): mixed
{
    return $GLOBALS['config'][$key] ?? $default;
}

/** Échappement HTML. */
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim((string) config('app_url'), '/') . '/' . ltrim($path, '/');
}

/** Domaine affiché (ex. : new.elitetap.ca), tiré de app_url. */
function public_host(): string
{
    return (string) parse_url((string) config('app_url'), PHP_URL_HOST);
}

/**
 * Le site peut-il être indexé par les moteurs de recherche ?
 * Oui seulement sur le domaine de production, sauf réglage 'indexable' explicite.
 * Évite que new.elitetap.ca (préproduction) entre en concurrence avec elitetap.ca.
 */
function is_indexable(): bool
{
    $forced = config('indexable');
    if (is_bool($forced)) {
        return $forced;
    }
    return in_array(public_host(), ['elitetap.ca', 'www.elitetap.ca'], true);
}

function asset(string $path): string
{
    $file = APP_ROOT . '/public/assets/' . ltrim($path, '/');
    $v = is_file($file) ? substr(md5((string) filemtime($file)), 0, 8) : '1';
    return '/assets/' . ltrim($path, '/') . '?v=' . $v;
}

function redirect(string $to, int $code = 302): never
{
    header('Location: ' . $to, true, $code);
    exit;
}

function view(string $name, array $data = [], ?string $layout = null): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require APP_DIR . '/views/' . $name . '.php';
    $content = ob_get_clean();
    if ($layout === null) {
        echo $content;
        return;
    }
    require APP_DIR . '/views/layouts/' . $layout . '.php';
}

function abort(int $code = 404): never
{
    http_response_code($code);
    view('errors/' . ($code === 404 ? '404' : 'error'), ['code' => $code], 'site');
    exit;
}

// ---------- CSRF ----------

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Session expirée. Rechargez la page et réessayez.');
    }
}

// ---------- Messages flash et anciennes valeurs ----------

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function with_old(array $data): void
{
    $_SESSION['old'] = $data;
}

function old(string $key, string $default = ''): string
{
    static $old = null;
    if ($old === null) {
        $old = $_SESSION['old'] ?? [];
        unset($_SESSION['old']);
    }
    $v = $old[$key] ?? $default;
    return is_string($v) ? $v : $default;
}

// ---------- Entrées ----------

function input(string $key, int $max = 255): string
{
    $v = $_POST[$key] ?? '';
    if (!is_string($v)) {
        return '';
    }
    $v = trim(str_replace("\0", '', $v));
    return mb_substr($v, 0, $max);
}

function valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/** Normalise une URL saisie par l'utilisateur ; n'accepte que http(s). */
function clean_url(string $u): ?string
{
    $u = trim($u);
    if ($u === '') {
        return '';
    }
    if (!preg_match('~^[a-z][a-z0-9+.-]*:~i', $u)) {
        $u = 'https://' . $u;
    }
    $scheme = strtolower((string) parse_url($u, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true) || filter_var($u, FILTER_VALIDATE_URL) === false) {
        return null;
    }
    return $u;
}

function clean_color(string $c, string $fallback): string
{
    return preg_match('/^#[0-9a-fA-F]{6}$/', $c) ? strtoupper($c) : $fallback;
}

function slugify(string $s): string
{
    if (function_exists('transliterator_transliterate')) {
        $s = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $s) ?: $s;
    } else {
        $s = (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    }
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim((string) $s, '-');
}

function unique_slug(string $base, ?int $ignoreProfileId = null): string
{
    $base = slugify($base) ?: 'profil';
    $base = substr($base, 0, 50);
    $slug = $base;
    $i = 2;
    while (true) {
        $id = scalar('SELECT id FROM profiles WHERE slug = ?', [$slug]);
        if ($id === null || (int) $id === $ignoreProfileId) {
            return $slug;
        }
        $slug = $base . '-' . $i++;
    }
}

function random_code(int $len = 8): string
{
    // Sans caractères ambigus (0/O, 1/I/L)
    $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $out = '';
    for ($i = 0; $i < $len; $i++) {
        $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return $out;
}

function money(?int $cents): string
{
    if ($cents === null) {
        return t('Sur devis');
    }
    return lang() === 'en'
        ? '$' . number_format($cents / 100, 2, '.', ',')
        : number_format($cents / 100, 2, ',', ' ') . ' $';
}

function date_fr(?string $d): string
{
    if (!$d) {
        return '—';
    }
    return date('d/m/Y H:i', strtotime($d));
}

function full_name(array $p): string
{
    return trim($p['first_name'] . ' ' . $p['last_name']);
}

function initials(array $p): string
{
    $f = mb_substr($p['first_name'], 0, 1);
    $l = mb_substr($p['last_name'], 0, 1);
    return mb_strtoupper(($f . $l) ?: 'ET');
}

/** Couleur de texte la plus lisible (noir ou blanc) sur un fond donné. */
function on_color(string $hex): string
{
    return contrast_ratio($hex, '#FFFFFF') >= contrast_ratio($hex, '#14161A') ? '#FFFFFF' : '#14161A';
}

const ORDER_STATUSES = [
    'devis'      => 'Devis',
    'design'     => 'Design en cours',
    'epreuve'    => 'Épreuve envoyée',
    'production' => 'En production',
    'expediee'   => 'Expédiée',
    'annulee'    => 'Annulée',
];

const CARD_STATUSES = ['stock' => 'En stock', 'active' => 'Active', 'disabled' => 'Désactivée'];

const CARD_TYPES = ['Standard', 'Premium', 'Bois', 'Métal', 'Je ne sais pas encore'];
