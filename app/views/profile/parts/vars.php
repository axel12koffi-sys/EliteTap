<?php
// Variables communes du profil (standard et sur mesure)
$name = full_name($p) ?: $p['company'];
$sub = implode(' · ', array_filter([$p['title'], $p['company'] !== $name ? $p['company'] : '']));
[$cover, $accent] = profile_colors($p);
$onAccent = on_color($accent);
[$fontLabel, $fontParam, $fontStack] = profile_font($p);
$logo = $p['logo_path'] && profile_can($p, 'brand') ? $p['logo_path'] : null;
$telHref = preg_replace('/[^0-9+]/', '', $p['phone']);
$actions = [];
if ($p['phone'] !== '') {
    $actions[] = ['tel:' . $telHref, t('Appeler'), '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z" stroke-linejoin="round"/>'];
    $actions[] = ['sms:' . $telHref, t('Texto'), '<path d="M4 20l1.3-3.9A8 8 0 1 1 8 19z" stroke-linejoin="round"/>'];
}
if ($p['whatsapp'] !== '') {
    $actions[] = ['https://wa.me/' . preg_replace('/\D/', '', $p['whatsapp']), 'WhatsApp', '<path d="M4 20l1.3-3.9A8 8 0 1 1 8 19z" stroke-linejoin="round"/><path d="M9 9.5c.5 2 2 3.5 4.5 4.5l1-1.2 2 .8-.3 1.6c-3.6.3-7.6-3.4-7.4-7l1.6-.3.8 2z" stroke-linejoin="round"/>'];
}
if ($p['email'] !== '') {
    $actions[] = ['mailto:' . $p['email'], t('Courriel'), '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>'];
}
if ($p['website'] !== '') {
    $actions[] = [$p['website'], t('Site web'), '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/>'];
}
if ($p['address'] !== '') {
    $actions[] = ['https://www.google.com/maps/search/?api=1&query=' . rawurlencode($p['address']), t('Itinéraire'), '<path d="M12 21c-5-4.5-7-8-7-11a7 7 0 0 1 14 0c0 3-2 6.5-7 11z"/><circle cx="12" cy="10" r="2.5"/>'];
}
?>
