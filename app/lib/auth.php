<?php
declare(strict_types=1);

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $id = $_SESSION['uid'] ?? null;
        $user = $id ? row('SELECT id, email, name, role FROM users WHERE id = ?', [$id]) : null;
    }
    return $user;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        $_SESSION['intended'] = $_SERVER['REQUEST_URI'] ?? '/compte';
        redirect('/connexion');
    }
    return $u;
}

function require_admin(): array
{
    $u = require_login();
    if ($u['role'] !== 'admin') {
        abort(403);
    }
    return $u;
}

function login_user(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $id;
    q('UPDATE users SET last_login_at = ? WHERE id = ?', [now(), $id]);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ---------- Limitation des tentatives (connexion, mot de passe oublié, devis) ----------

function throttle_key(string $scope, string $extra = ''): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0';
    return hash('sha256', $scope . '|' . $ip . '|' . strtolower($extra) . '|' . config('secret'));
}

function too_many_attempts(string $key, int $max, int $minutes): bool
{
    $since = date('Y-m-d H:i:s', time() - $minutes * 60);
    $n = (int) scalar('SELECT COUNT(*) FROM login_attempts WHERE key_hash = ? AND created_at > ?', [$key, $since]);
    return $n >= $max;
}

function hit_attempt(string $key): void
{
    q('INSERT INTO login_attempts (key_hash, created_at) VALUES (?, ?)', [$key, now()]);
    // Nettoyage occasionnel
    if (random_int(1, 50) === 1) {
        q('DELETE FROM login_attempts WHERE created_at < ?', [date('Y-m-d H:i:s', time() - 86400)]);
    }
}

function clear_attempts(string $key): void
{
    q('DELETE FROM login_attempts WHERE key_hash = ?', [$key]);
}

/** Crée un compte client + son profil. Renvoie [user_id, mot de passe temporaire]. */
function create_client(string $name, string $email): array
{
    $password = random_code(10);
    q('INSERT INTO users (email, password_hash, name, role, created_at) VALUES (?, ?, ?, ?, ?)', [
        strtolower($email), password_hash($password, PASSWORD_DEFAULT), $name, 'client', now(),
    ]);
    $userId = (int) db()->lastInsertId();

    $parts = preg_split('/\s+/', trim($name), 2);
    $first = $parts[0] ?? '';
    $last = $parts[1] ?? '';
    q('INSERT INTO profiles (user_id, slug, first_name, last_name, email, created_at, updated_at)
       VALUES (?, ?, ?, ?, ?, ?, ?)', [
        $userId, unique_slug($name), $first, $last, strtolower($email), now(), now(),
    ]);
    return [$userId, $password];
}
