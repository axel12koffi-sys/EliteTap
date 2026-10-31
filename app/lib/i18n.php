<?php
declare(strict_types=1);

const LANGS = ['fr', 'en'];

/**
 * Langue courante. Ordre : forcée (courriels) > ?lang= > témoin > compte > navigateur > français.
 */
function lang(): string
{
    if (isset($GLOBALS['lang_override'])) {
        return $GLOBALS['lang_override'];
    }
    static $lang = null;
    if ($lang !== null) {
        return $lang;
    }
    $q = $_GET['lang'] ?? null;
    if (is_string($q) && in_array($q, LANGS, true)) {
        $lang = $q;
        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            setcookie('lang', $lang, ['expires' => time() + 31536000, 'path' => '/', 'samesite' => 'Lax', 'httponly' => true,
                'secure' => str_starts_with((string) config('app_url'), 'https://')]);
        }
        if (!empty($_SESSION['uid'])) {
            q('UPDATE users SET lang = ? WHERE id = ?', [$lang, $_SESSION['uid']]);
        }
        return $lang;
    }
    $c = $_COOKIE['lang'] ?? null;
    if (is_string($c) && in_array($c, LANGS, true)) {
        return $lang = $c;
    }
    if (!empty($_SESSION['uid'])) {
        $u = scalar('SELECT lang FROM users WHERE id = ?', [$_SESSION['uid']]);
        if (in_array($u, LANGS, true)) {
            return $lang = $u;
        }
    }
    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
    return $lang = str_starts_with($accept, 'en') ? 'en' : 'fr';
}

/** Traduit une chaîne française. {nom} est remplacé par $vars['nom']. */
function t(string $fr, array $vars = []): string
{
    static $dict = null;
    $s = $fr;
    if (lang() === 'en') {
        $dict ??= require APP_DIR . '/lang/en.php';
        $s = $dict[$fr] ?? $fr;
    }
    foreach ($vars as $k => $v) {
        $s = str_replace('{' . $k . '}', (string) $v, $s);
    }
    return $s;
}

/** Exécute $fn dans une langue donnée (ex. : courriel au client dans sa langue). */
function in_lang(string $lang, callable $fn): mixed
{
    $prev = $GLOBALS['lang_override'] ?? null;
    $GLOBALS['lang_override'] = in_array($lang, LANGS, true) ? $lang : 'fr';
    try {
        return $fn();
    } finally {
        if ($prev === null) {
            unset($GLOBALS['lang_override']);
        } else {
            $GLOBALS['lang_override'] = $prev;
        }
    }
}

/** URL de la page courante dans l'autre langue. */
function switch_lang_url(): string
{
    $other = lang() === 'fr' ? 'en' : 'fr';
    $path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/';
    $query = $_GET;
    $query['lang'] = $other;
    return $path . '?' . http_build_query($query);
}

function lang_switch_label(): string
{
    return lang() === 'fr' ? 'English' : 'Français';
}

/** Traduit et échappe pour le HTML. */
function te(string $fr, array $vars = []): string
{
    return e(t($fr, $vars));
}
