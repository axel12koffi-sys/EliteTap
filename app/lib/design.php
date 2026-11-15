<?php
declare(strict_types=1);

/*
 * Profil sur mesure : un gabarit HTML/CSS (généré par l'IA à partir du design de la carte, retouché
 * et validé par l'admin) remplace la mise en page standard du profil public.
 *  - Le contenu reste celui du profil : le gabarit n'a que des emplacements ({{nom_complet}}, {{boutons}}…).
 *  - Sécurité : liste blanche de balises, d'attributs et d'adresses ; aucun script ; page servie avec une CSP stricte.
 *  - Les éléments de la plateforme (enregistrer le contact, actions, liens, prospects, Wallet) sont toujours insérés :
 *    s'ils manquent dans le gabarit, ils sont ajoutés à la fin.
 */

const DESIGN_SRC_DIR = APP_ROOT . '/storage/uploads/card-designs';
const DESIGN_ASSET_DIR = APP_ROOT . '/public/uploads/custom';
const DESIGN_SRC_TYPES = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];
const DESIGN_MAX_HTML = 60000;
const DESIGN_MAX_CSS = 60000;

/** Emplacements de texte (échappés) et d'adresses d'image. */
const DESIGN_FIELDS = [
    'prenom' => 'Prénom', 'nom' => 'Nom de famille', 'nom_complet' => 'Prénom et nom (ou entreprise)', 'initiales' => 'Initiales',
    'poste' => 'Poste', 'entreprise' => 'Entreprise', 'bio' => 'Présentation', 'telephone' => 'Téléphone', 'courriel' => 'Courriel',
    'site' => 'Site web (affichage)', 'adresse' => 'Adresse', 'photo' => 'Adresse de la photo', 'logo' => 'Adresse du logo',
];
/** Emplacements de composants de la plateforme (HTML inséré par EliteTap). */
const DESIGN_COMPONENTS = [
    'bouton_contact' => 'Bouton « Enregistrer le contact »', 'actions' => 'Boutons Appeler, Texto, WhatsApp, Courriel, Site, Itinéraire',
    'wallet' => 'Boutons Apple / Google Wallet (Pro)', 'liens' => 'Liste des liens du client', 'prospects' => 'Formulaire « Laissez vos coordonnées » (Pro)',
];
const DESIGN_REQUIRED = ['bouton_contact', 'actions', 'liens', 'prospects'];

function design_ready(): bool
{
    static $ok = null;
    if ($ok === null) {
        try {
            scalar('SELECT COUNT(*) FROM profile_designs WHERE 1 = 0');
            $ok = true;
        } catch (Throwable $e) {
            $ok = false;
        }
    }
    return $ok;
}

/** Design publié et actif pour ce profil, sinon null. */
function active_design(array $p): ?array
{
    if (empty($p['custom_design_id']) || empty($p['use_custom']) || !design_ready()) {
        return null;
    }
    return row("SELECT * FROM profile_designs WHERE id = ? AND profile_id = ? AND status = 'published'", [$p['custom_design_id'], $p['id']]);
}

/* ------------------------------------------------------------------ */
/* Filtre de sécurité                                                  */
/* ------------------------------------------------------------------ */

const SAFE_TAGS = ['div', 'span', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'strong', 'em', 'b', 'i', 'u', 's', 'small', 'br', 'hr',
    'img', 'a', 'section', 'header', 'footer', 'main', 'article', 'aside', 'nav', 'ul', 'ol', 'li', 'figure', 'figcaption',
    'blockquote', 'address', 'picture', 'sup', 'sub', 'mark', 'abbr', 'time',
    'svg', 'g', 'path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon', 'defs', 'lineargradient', 'radialgradient', 'stop', 'text', 'tspan', 'title', 'desc'];
/** Balises supprimées avec leur contenu. */
const DROP_TAGS = ['script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet', 'form', 'input', 'button', 'textarea',
    'select', 'option', 'link', 'meta', 'base', 'noscript', 'template', 'video', 'audio', 'source', 'track', 'canvas', 'math',
    'foreignobject', 'use', 'image', 'animate', 'animatetransform', 'animatemotion', 'set', 'portal', 'dialog'];
const SAFE_ATTRS = ['class', 'id', 'style', 'title', 'role', 'alt', 'src', 'href', 'width', 'height', 'loading', 'dir', 'lang', 'datetime',
    'viewbox', 'd', 'fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'stroke-dasharray', 'cx', 'cy', 'r', 'rx', 'ry',
    'x', 'y', 'x1', 'y1', 'x2', 'y2', 'points', 'transform', 'opacity', 'fill-opacity', 'stroke-opacity', 'offset', 'stop-color',
    'stop-opacity', 'gradientunits', 'gradienttransform', 'xmlns', 'preserveaspectratio', 'fill-rule', 'clip-rule', 'font-size',
    'font-weight', 'text-anchor', 'dominant-baseline', 'letter-spacing', 'font-family'];

/** Adresse d'image autorisée : fichiers du site, image intégrée, emplacement. */
function safe_img_src(string $v): bool
{
    $v = trim($v);
    return (bool) preg_match('#^(/uploads/[A-Za-z0-9/_.-]+|data:image/(png|jpeg|webp|gif);base64,[A-Za-z0-9+/=\s]+|\{\{(photo|logo|asset:[a-z0-9_-]+)\}\})$#i', $v);
}

/** Lien autorisé : https, téléphone, courriel, ancre, emplacement. */
function safe_href(string $v): bool
{
    $v = trim($v);
    return (bool) preg_match('#^(https://[^\s"<>]+|tel:[0-9+() .-]+|mailto:[^\s"<>]+|\#[A-Za-z0-9_-]*|\{\{(site|telephone|courriel)\}\})$#i', $v);
}

/**
 * Nettoie le CSS : pas d'import (sauf Google Fonts), pas d'URL externes (sauf polices Google), pas d'expressions.
 * @return array{0: string, 1: string[]} CSS nettoyé, éléments retirés
 */
function sanitize_css(string $css, bool $inline = false): array
{
    $removed = [];
    $css = mb_substr($css, 0, DESIGN_MAX_CSS);
    $css = preg_replace('#/\*.*?\*/#s', '', $css);
    if (str_contains($css, '\\')) {
        $removed[] = 'échappements CSS (\\)';
        $css = str_replace('\\', '', $css);
    }
    foreach (['</', '<!--', '-->'] as $bad) {
        if (str_contains($css, $bad)) {
            $removed[] = "séquence « $bad »";
            $css = str_replace($bad, '', $css);
        }
    }
    $css = preg_replace_callback('/@import\s+(url\()?\s*["\']?([^"\')\s;]+)["\']?\s*\)?\s*;?/i', function ($m) use (&$removed, $inline) {
        if (!$inline && preg_match('#^https://fonts\.googleapis\.com/css2?\?[A-Za-z0-9+=&:;@,._%-]+$#', $m[2])) {
            return '@import url("' . $m[2] . '");';
        }
        $removed[] = '@import ' . mb_strimwidth($m[2], 0, 60, '…');
        return '';
    }, $css);
    $css = preg_replace_callback('/url\(\s*(["\']?)(.*?)\1\s*\)/i', function ($m) use (&$removed, $inline) {
        $u = trim($m[2]);
        if (preg_match('#^(/uploads/[A-Za-z0-9/_.-]+|data:image/(png|jpeg|webp|gif|svg\+xml);base64,[A-Za-z0-9+/=]+|https://fonts\.gstatic\.com/[A-Za-z0-9/_.-]+|\{\{(photo|logo|asset:[a-z0-9_-]+)\}\})$#i', $u)
            || (!$inline && preg_match('#^https://fonts\.googleapis\.com/css2?\?[A-Za-z0-9+=&:;@,._%-]+$#', $u))) {
            return 'url("' . $u . '")';
        }
        $removed[] = 'url(' . mb_strimwidth($u, 0, 60, '…') . ')';
        return 'none';
    }, $css);
    foreach (['/expression\s*\(/i' => 'expression()', '/javascript\s*:/i' => 'javascript:', '/behavior\s*:/i' => 'behavior:',
        '/-moz-binding/i' => '-moz-binding', '/@charset[^;]*;?/i' => '@charset', '/@namespace[^;]*;?/i' => '@namespace'] as $re => $label) {
        if (preg_match($re, $css)) {
            $removed[] = $label;
            $css = preg_replace($re, '', $css);
        }
    }
    if ($inline) {
        $css = str_replace(['{', '}', '@'], '', $css);
    }
    return [trim($css), array_values(array_unique($removed))];
}

/**
 * Nettoie le HTML du gabarit (liste blanche).
 * @return array{0: string, 1: string[]} HTML nettoyé, éléments retirés
 */
function sanitize_template(string $html): array
{
    $removed = [];
    $html = mb_substr($html, 0, DESIGN_MAX_HTML);
    if (trim($html) === '') {
        return ['', []];
    }
    $doc = new DOMDocument();
    $prev = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    $root = $doc->getElementById('__root');
    if (!$root) {
        return ['', ['gabarit illisible']];
    }
    $walk = function (DOMNode $node) use (&$walk, &$removed, $doc) {
        for ($child = $node->firstChild; $child !== null; $child = $next) {
            $next = $child->nextSibling;
            if ($child instanceof DOMComment || $child instanceof DOMProcessingInstruction || $child instanceof DOMCdataSection) {
                $node->removeChild($child);
                continue;
            }
            if (!$child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->localName ?? $child->nodeName);
            if (in_array($tag, DROP_TAGS, true)) {
                $removed[] = "<$tag>";
                $node->removeChild($child);
                continue;
            }
            if (!in_array($tag, SAFE_TAGS, true)) {
                // Balise inconnue : on garde son contenu
                $removed[] = "<$tag> (contenu gardé)";
                $walk($child);
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->nodeName);
                $val = $attr->nodeValue ?? '';
                $ok = in_array($name, SAFE_ATTRS, true) || str_starts_with($name, 'aria-') || str_starts_with($name, 'data-');
                if ($ok && str_starts_with($name, 'on')) {
                    $ok = false;
                }
                if ($ok && $name === 'src') {
                    $ok = $tag === 'img' && safe_img_src($val);
                }
                if ($ok && $name === 'href') {
                    $ok = $tag === 'a' && safe_href($val);
                }
                if ($ok && $name === 'style') {
                    [$clean, $r] = sanitize_css($val, true);
                    $removed = array_merge($removed, $r);
                    $child->setAttribute('style', $clean);
                    continue;
                }
                if ($ok && preg_match('/javascript:|vbscript:|data:text|\{\{(bouton_contact|actions|wallet|liens|prospects)\}\}/i', $val)) {
                    $ok = false;
                }
                if (!$ok) {
                    $removed[] = "attribut $name" . ($name === 'src' || $name === 'href' ? ' « ' . mb_strimwidth($val, 0, 50, '…') . ' »' : '');
                    $child->removeAttribute($attr->nodeName);
                }
            }
            if ($tag === 'a' && $child->hasAttribute('href') && str_starts_with(strtolower($child->getAttribute('href')), 'https://')) {
                $child->setAttribute('rel', 'noopener noreferrer');
                $child->setAttribute('target', '_blank');
            }
            if ($tag === 'img' && !$child->hasAttribute('src')) {
                $node->removeChild($child);
                continue;
            }
            if ($tag === 'img' && !$child->hasAttribute('alt')) {
                $child->setAttribute('alt', '');
            }
            $walk($child);
        }
    };
    $walk($root);
    $out = '';
    foreach ($root->childNodes as $n) {
        $out .= $doc->saveHTML($n);
    }
    // Les accolades des emplacements sont parfois encodées par le moteur HTML
    $out = str_replace(['%7B%7B', '%7D%7D'], ['{{', '}}'], $out);
    return [trim($out), array_values(array_unique($removed))];
}

/** Vérifications du gabarit avant publication : emplacements essentiels. */
function design_checks(string $html): array
{
    $warn = [];
    foreach (DESIGN_REQUIRED as $c) {
        if (!str_contains($html, '{{' . $c . '}}')) {
            $warn[] = "{{{$c}}} absent : " . DESIGN_COMPONENTS[$c] . ' sera ajouté à la fin du profil.';
        }
    }
    if (!str_contains($html, '{{nom_complet}}') && !str_contains($html, '{{prenom}}')) {
        $warn[] = 'Le nom du client n\'apparaît pas ({{nom_complet}} ou {{prenom}} {{nom}}).';
    }
    preg_match_all('/\{\{\s*([#\/]?)([a-z_:0-9-]+)\s*\}\}/i', $html, $m);
    foreach (array_unique($m[2]) as $k) {
        if (!isset(DESIGN_FIELDS[$k]) && !isset(DESIGN_COMPONENTS[$k]) && !str_starts_with($k, 'asset:')) {
            $warn[] = "Emplacement inconnu {{{$k}}} : il sera retiré.";
        }
    }
    return $warn;
}

/* ------------------------------------------------------------------ */
/* Rendu                                                               */
/* ------------------------------------------------------------------ */

/** Valeurs texte des emplacements pour un profil. */
function design_values(array $p): array
{
    $name = full_name($p) ?: $p['company'];
    return [
        'prenom' => $p['first_name'], 'nom' => $p['last_name'], 'nom_complet' => $name, 'initiales' => initials($p),
        'poste' => $p['title'], 'entreprise' => $p['company'], 'bio' => $p['bio'], 'telephone' => $p['phone'],
        'courriel' => $p['email'], 'site' => preg_replace('#^https?://(www\.)?#', '', rtrim($p['website'], '/')),
        'adresse' => $p['address'],
        'photo' => $p['avatar_path'] ? '/uploads/avatars/' . $p['avatar_path'] : '',
        'logo' => $p['logo_path'] && profile_can($p, 'brand') ? '/uploads/avatars/' . $p['logo_path'] : '',
    ];
}

/**
 * Remplit le gabarit : sections conditionnelles {{#champ}}…{{/champ}}, valeurs échappées, composants.
 * $components : nom → HTML de la plateforme. Les composants essentiels absents sont ajoutés à la fin.
 */
function render_design(string $html, array $p, array $components, array $assets = []): string
{
    $values = design_values($p);
    // Liens dans les attributs href : adresse cliquable correspondante
    $hrefs = ['site' => $p['website'], 'telephone' => 'tel:' . preg_replace('/[^0-9+]/', '', $p['phone']), 'courriel' => 'mailto:' . $p['email']];
    $html = preg_replace_callback('/href="\{\{(site|telephone|courriel)\}\}"/', fn($m) => 'href="' . e($hrefs[$m[1]]) . '"', $html);
    // Sections conditionnelles (jusqu'à 3 niveaux)
    for ($i = 0; $i < 3; $i++) {
        $html = preg_replace_callback('/\{\{#([a-z_]+)\}\}(.*?)\{\{\/\1\}\}/s', function ($m) use ($values, $components) {
            $has = isset($values[$m[1]]) ? trim((string) $values[$m[1]]) !== '' : trim((string) ($components[$m[1]] ?? '')) !== '';
            return $has ? $m[2] : '';
        }, $html);
    }
    $used = [];
    $html = preg_replace_callback('/\{\{\s*([a-z_]+|asset:[a-z0-9_-]+)\s*\}\}/i', function ($m) use ($values, $components, $assets, &$used) {
        $k = $m[1];
        if (str_starts_with($k, 'asset:')) {
            return e($assets[substr($k, 6)] ?? '');
        }
        if (array_key_exists($k, $components)) {
            $used[$k] = true;
            return $components[$k];
        }
        return array_key_exists($k, $values) ? e((string) $values[$k]) : '';
    }, $html);
    $missing = '';
    foreach (DESIGN_REQUIRED as $c) {
        if (empty($used[$c]) && trim($components[$c] ?? '') !== '') {
            $missing .= $components[$c];
        }
    }
    return $html . ($missing !== '' ? '<div class="et-essentials">' . $missing . '</div>' : '');
}

function design_assets(int $profileId): array
{
    $out = [];
    if (design_ready()) {
        foreach (rows('SELECT name, path FROM profile_design_assets WHERE profile_id = ? ORDER BY name', [$profileId]) as $a) {
            $out[$a['name']] = '/uploads/custom/' . $a['path'];
        }
    }
    return $out;
}

/** CSP des pages de profil sur mesure : aucun script, ressources du site et polices Google seulement. */
function design_csp_header(): void
{
    header("Content-Security-Policy: default-src 'none'; script-src 'none'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
        . "font-src 'self' https://fonts.gstatic.com data:; img-src 'self' data:; form-action 'self'; base-uri 'none'; "
        . "frame-ancestors 'self'; object-src 'none'; connect-src 'none'");
}

/* ------------------------------------------------------------------ */
/* IA (API Claude d'Anthropic)                                         */
/* ------------------------------------------------------------------ */

function ai_enabled(): bool
{
    return !empty(config('anthropic')['api_key']);
}

function ai_system_prompt(): string
{
    $fields = implode("\n", array_map(fn($k, $v) => "- {{{$k}}} : $v", array_keys(DESIGN_FIELDS), DESIGN_FIELDS));
    $comps = implode("\n", array_map(fn($k, $v) => "- {{{$k}}} : $v", array_keys(DESIGN_COMPONENTS), DESIGN_COMPONENTS));
    return <<<TXT
Tu es intégrateur web pour EliteTap, des cartes d'affaires NFC. On te donne le design de la carte physique d'un client.
Tu crées la page de profil mobile qui s'ouvre quand on touche la carte, dans le même style visuel (couleurs, typographie, formes, ambiance).

Règles strictes :
1. Réponds uniquement avec deux blocs : <html_template>…</html_template> puis <css>…</css>. Aucun autre texte.
2. HTML : seulement le contenu de la page (pas de <html>, <head>, <body>, <style>, <script>). Aucun JavaScript, aucun attribut on*, aucun formulaire, aucune iframe.
3. N'écris jamais les informations du client en dur : utilise ces emplacements, ils sont remplis par la plateforme et échappés :
{$fields}
   Sections facultatives : {{#photo}}…{{/photo}} n'apparaît que si la photo existe (idem pour logo, poste, entreprise, bio, adresse).
   Images : <img src="{{photo}}" alt="…"> ou <img src="{{logo}}" alt="…">. Images d'habillage fournies : <img src="{{asset:nom}}">.
4. Place ces éléments de la plateforme (HTML déjà prêt, ne les recrée pas), chacun seul dans son propre élément :
{$comps}
   {{bouton_contact}}, {{actions}}, {{liens}} et {{prospects}} sont obligatoires.
5. CSS : mobile d'abord (largeur 360 à 480 px), centré sur grand écran (max-width environ 480px). Préfixe toutes les règles par .et-custom.
   Chaque élément de la plateforme est livré dans un <div class="et-block et-block-…"> (largeur max 480px, marges 0 20px) : tu peux ajuster .et-custom .et-block.
   Pour colorer les éléments de la plateforme, définis sur .et-custom les variables --accent (fond du bouton principal), --on-accent (texte sur l'accent),
   --card (fond des boutons et liens), --line (bordures), --text et --muted. Tu peux aussi styler .save, .actions a, .links a, .lead, .wallet-btn.
   Polices : uniquement Google Fonts, avec @import url("https://fonts.googleapis.com/css2?family=…&display=swap"); en première ligne. Aucune autre URL externe.
6. Lisible et accessible : contraste du texte d'au moins 4,5:1, texte d'au moins 14 px, zones tactiles d'au moins 44 px, alt sur les images.
7. Pas de texte inventé (pas de slogan, pas de faux chiffres). Pas d'emoji.
TXT;
}

/**
 * Appelle l'API Claude avec le design (image ou PDF). Mode correction si $current est fourni.
 * @return array{html: string, css: string}
 */
function ai_generate_design(string $sourceFile, string $mime, array $p, array $assets, string $instruction = '', ?array $current = null): array
{
    $c = config('anthropic');
    $data = base64_encode((string) file_get_contents($sourceFile));
    $media = $mime === 'application/pdf'
        ? ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $data]]
        : ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => $data]];
    $sample = json_encode(array_filter(design_values($p), fn($v) => $v !== ''), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $assetList = $assets ? implode(', ', array_map(fn($k) => "{{asset:$k}}", array_keys($assets))) : 'aucune';
    $text = "Design de la carte ci-joint.\nInformations actuelles du client (pour juger des longueurs, ne pas les écrire en dur) :\n$sample\nImages d'habillage disponibles : $assetList.\n";
    if ($current && trim((string) $current['html']) !== '') {
        $text .= "\nVoici le gabarit actuel à corriger :\n<html_template>\n{$current['html']}\n</html_template>\n<css>\n{$current['css']}\n</css>\n"
            . "\nCorrection demandée par l'administrateur : " . ($instruction !== '' ? $instruction : 'améliore la fidélité au design de la carte.')
            . "\nRenvoie le gabarit complet corrigé.";
    } else {
        $text .= "\nCrée le gabarit." . ($instruction !== '' ? "\nIndications de l'administrateur : $instruction" : '');
    }
    $payload = [
        'model' => $c['model'] ?? 'claude-sonnet-5-5',
        'max_tokens' => (int) ($c['max_tokens'] ?? 12000),
        'system' => ai_system_prompt(),
        'messages' => [['role' => 'user', 'content' => [$media, ['type' => 'text', 'text' => $text]]]],
    ];
    @set_time_limit(180);
    $ch = curl_init(rtrim($c['base_url'] ?? 'https://api.anthropic.com', '/') . '/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 170,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['content-type: application/json', 'anthropic-version: 2023-06-01', 'x-api-key: ' . $c['api_key']],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($res === false) {
        throw new RuntimeException('Connexion à l\'IA impossible : ' . $err);
    }
    $j = json_decode((string) $res, true);
    if ($code !== 200) {
        $msg = $j['error']['message'] ?? ('réponse ' . $code);
        throw new RuntimeException('L\'IA a refusé la demande : ' . $msg);
    }
    $out = '';
    foreach ($j['content'] ?? [] as $block) {
        if (($block['type'] ?? '') === 'text') {
            $out .= $block['text'];
        }
    }
    if (!preg_match('#<html_template>(.*?)</html_template>#s', $out, $h) || !preg_match('#<css>(.*?)</css>#s', $out, $s)) {
        throw new RuntimeException('Réponse de l\'IA incomplète' . (($j['stop_reason'] ?? '') === 'max_tokens' ? ' (trop longue, augmentez max_tokens)' : '') . '. Réessayez.');
    }
    return ['html' => trim($h[1]), 'css' => trim($s[1])];
}
