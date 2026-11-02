<?php
declare(strict_types=1);

const DESIGN_DIR = APP_ROOT . '/public/uploads/designs';

/** Designs visibles, dans l'ordre (vide si la migration 004 n'est pas encore faite). */
function public_designs(): array
{
    try {
        return rows('SELECT * FROM designs WHERE is_visible = 1 ORDER BY position, id');
    } catch (Throwable $e) {
        return [];
    }
}

/** Redimensionne une image (largeur max) et l'enregistre en JPEG. */
function save_jpeg($src, int $maxW, string $path, int $quality = 82): void
{
    $w = imagesx($src);
    $h = imagesy($src);
    $scale = min(1, $maxW / $w);
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));
    $dst = imagecreatetruecolor($nw, $nh);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagejpeg($dst, $path, $quality);
    imagedestroy($dst);
}

function admin_designs(): void
{
    $user = require_admin();
    $designs = [];
    try {
        $designs = rows('SELECT * FROM designs ORDER BY position, id');
    } catch (Throwable $e) {
        flash('error', 'Table designs absente : exécutez la migration 004 dans phpMyAdmin.');
    }
    view('admin/designs', ['title' => 'Designs | Admin EliteTap', 'nav' => 'designs', 'user' => $user, 'designs' => $designs], 'admin');
}

/** Envoi d'une ou plusieurs photos. */
function admin_designs_upload(): void
{
    require_admin();
    $material = in_array($_POST['material'] ?? '', ['Standard', 'Premium', 'Bois', 'Métal'], true) ? $_POST['material'] : 'Standard';
    $title = input('title', 120);
    $files = $_FILES['photos'] ?? null;
    $count = 0;
    $errors = 0;
    if ($files && is_array($files['name'])) {
        if (!is_dir(DESIGN_DIR)) mkdir(DESIGN_DIR, 0755, true);
        $pos = (int) scalar('SELECT COALESCE(MAX(position), 0) FROM designs');
        foreach ($files['tmp_name'] as $i => $tmp) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || $files['size'][$i] > 15 * 1024 * 1024) {
                if (($files['error'][$i] ?? 0) !== UPLOAD_ERR_NO_FILE) $errors++;
                continue;
            }
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
            $src = match ($mime) {
                'image/jpeg' => @imagecreatefromjpeg($tmp),
                'image/png'  => @imagecreatefrompng($tmp),
                'image/webp' => @imagecreatefromwebp($tmp),
                default      => false,
            };
            if (!$src) {
                $errors++;
                continue;
            }
            $base = bin2hex(random_bytes(10));
            save_jpeg($src, 1800, DESIGN_DIR . "/$base.jpg");
            save_jpeg($src, 720, DESIGN_DIR . "/$base-t.jpg", 80);
            imagedestroy($src);
            $label = $title !== '' ? $title : pathinfo((string) $files['name'][$i], PATHINFO_FILENAME);
            q('INSERT INTO designs (title, material, image_path, thumb_path, position, is_visible, created_at) VALUES (?, ?, ?, ?, ?, 1, ?)',
                [mb_substr($label, 0, 120), $material, "$base.jpg", "$base-t.jpg", ++$pos, now()]);
            $count++;
        }
    }
    if ($count) flash('success', "$count photo(s) ajoutée(s).");
    if ($errors) flash('error', "$errors fichier(s) ignoré(s) : formats acceptés JPG, PNG, WEBP, 15 Mo max.");
    if (!$count && !$errors) flash('error', 'Choisissez au moins une photo.');
    redirect('/admin/designs');
}

/** Modifier, masquer, déplacer ou supprimer un design. */
function admin_design_save(string $id): void
{
    require_admin();
    $d = row('SELECT * FROM designs WHERE id = ?', [(int) $id]);
    if (!$d) abort(404);
    $action = input('action', 10);
    if ($action === 'delete') {
        @unlink(DESIGN_DIR . '/' . basename($d['image_path']));
        @unlink(DESIGN_DIR . '/' . basename($d['thumb_path']));
        q('DELETE FROM designs WHERE id = ?', [$d['id']]);
        flash('success', 'Photo supprimée.');
    } elseif ($action === 'up' || $action === 'down') {
        $ids = array_map('intval', array_column(rows('SELECT id FROM designs ORDER BY position, id'), 'id'));
        $i = array_search((int) $d['id'], $ids, true);
        $j = $action === 'up' ? $i - 1 : $i + 1;
        if ($i !== false && isset($ids[$j])) {
            [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
            foreach ($ids as $pos => $did) q('UPDATE designs SET position = ? WHERE id = ?', [$pos + 1, $did]);
        }
    } else {
        $material = in_array($_POST['material'] ?? '', ['Standard', 'Premium', 'Bois', 'Métal'], true) ? $_POST['material'] : $d['material'];
        q('UPDATE designs SET title = ?, material = ?, is_visible = ? WHERE id = ?',
            [input('title', 120), $material, isset($_POST['is_visible']) ? 1 : 0, $d['id']]);
        flash('success', 'Design mis à jour.');
    }
    redirect('/admin/designs');
}
