<?php
declare(strict_types=1);

/*
 * Forfaits (plan d'évolution EliteTap, section 05)
 *  - Essentiel : inclus avec chaque carte, gratuit à vie. Profil hébergé, thèmes, vCard, cartes.
 *                Les modifications du profil sont faites par EliteTap (admin › Gérer le profil).
 *  - Pro       : abonnement. Modification autonome, statistiques, prospects, alertes, export,
 *                couleurs / logo / police de la marque.
 *  - Entreprise: sur devis. Tout le Pro (gestion des équipes : phase 3).
 * Réglage 'plans_enforced' => false dans config.php : toutes les fonctions pour tous.
 */

const PLANS = ['essentiel' => 'Essentiel', 'pro' => 'Pro', 'entreprise' => 'Entreprise'];

/** Fonctions réservées aux forfaits Pro et Entreprise. */
const PRO_FEATURES = ['edit', 'stats', 'leads', 'brand', 'wallet'];

const THEMES = [
    'marine'   => ['Marine',      '#14254A', '#3666AF'],
    'noir-or'  => ['Noir & or',   '#111111', '#C9A24A'],
    'blanc'    => ['Blanc épuré', '#E9E7E2', '#14161A'],
    'foret'    => ['Forêt',       '#1F4D3A', '#2F7A5B'],
    'bordeaux' => ['Bordeaux',    '#5E1E2B', '#8E2C3F'],
    'ciel'     => ['Ciel',        '#86B3E0', '#3666AF'],
];

/** [libellé, paramètre Google Fonts, pile CSS des titres] */
const FONTS = [
    'moderne'  => ['Moderne',  'Space+Grotesk:wght@600;700', "'Space Grotesk', sans-serif"],
    'classique' => ['Classique', 'Playfair+Display:wght@600;700', "'Playfair Display', Georgia, serif"],
    'elegante' => ['Élégante', 'Cormorant+Garamond:wght@600;700', "'Cormorant Garamond', Georgia, serif"],
];

function plans_enforced(): bool
{
    return config('plans_enforced', true) !== false;
}

function plan_is_paid(?string $plan): bool
{
    return in_array($plan, ['pro', 'entreprise'], true);
}

/** Le forfait d'un utilisateur donne-t-il accès à cette fonction ? */
function user_can(array $user, string $feature): bool
{
    if (!plans_enforced() || !in_array($feature, PRO_FEATURES, true)) {
        return true;
    }
    if (($user['role'] ?? '') === 'admin') {
        return true;
    }
    $plan = $user['plan'] ?? scalar('SELECT plan FROM users WHERE id = ?', [$user['id']]);
    return plan_is_paid($plan);
}

/** Même question, vue depuis un profil public (le forfait est celui du propriétaire). */
function profile_can(array $profile, string $feature): bool
{
    static $cache = [];
    $uid = (int) $profile['user_id'];
    $cache[$uid] ??= row('SELECT id, role, plan FROM users WHERE id = ?', [$uid]) ?? ['id' => $uid, 'role' => 'client', 'plan' => 'essentiel'];
    return user_can($cache[$uid], $feature);
}

/** Couleurs effectives du profil : thème choisi, ou couleurs de marque (Pro). */
function profile_colors(array $p): array
{
    if ($p['theme'] === 'custom' && profile_can($p, 'brand')) {
        return [$p['cover_color'], $p['accent_color']];
    }
    $t = THEMES[$p['theme']] ?? THEMES['marine'];
    return [$t[1], $t[2]];
}

function profile_font(array $p): array
{
    $key = profile_can($p, 'brand') && isset(FONTS[$p['font']]) ? $p['font'] : 'moderne';
    return FONTS[$key];
}

/** Rapport de contraste WCAG entre deux couleurs hexadécimales. */
function contrast_ratio(string $a, string $b): float
{
    $lum = function (string $hex): float {
        $hex = ltrim($hex, '#');
        [$r, $g, $bl] = array_map(fn($c) => hexdec($c) / 255, str_split($hex, 2));
        $lin = fn($c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        return 0.2126 * $lin($r) + 0.7152 * $lin($g) + 0.0722 * $lin($bl);
    };
    $l1 = $lum($a);
    $l2 = $lum($b);
    return (max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05);
}

/** Texte lisible sur un bouton de cette couleur : 4,5:1 minimum (WCAG AA). */
function color_readable(string $hex): bool
{
    return contrast_ratio($hex, on_color($hex)) >= 4.5;
}
