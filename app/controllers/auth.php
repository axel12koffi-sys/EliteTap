<?php
declare(strict_types=1);

function auth_login_form(): void
{
    if (current_user()) {
        redirect(current_user()['role'] === 'admin' ? '/admin' : '/compte');
    }
    view('auth/login', ['title' => 'Connexion | EliteTap'], 'auth');
}

function auth_login(): void
{
    $email = strtolower(input('email', 190));
    $password = $_POST['password'] ?? '';
    $key = throttle_key('login', $email);

    if (too_many_attempts($key, 8, 15)) {
        flash('error', 'Trop de tentatives. Réessayez dans 15 minutes.');
        with_old(['email' => $email]);
        redirect('/connexion');
    }

    $user = row('SELECT id, password_hash, role FROM users WHERE email = ?', [$email]);
    if (!$user || !is_string($password) || !password_verify($password, $user['password_hash'])) {
        hit_attempt($key);
        flash('error', 'Courriel ou mot de passe incorrect.');
        with_old(['email' => $email]);
        redirect('/connexion');
    }

    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
    }
    clear_attempts($key);
    login_user((int) $user['id']);

    $to = $_SESSION['intended'] ?? ($user['role'] === 'admin' ? '/admin' : '/compte');
    unset($_SESSION['intended']);
    // Redirection interne uniquement
    if (!is_string($to) || !str_starts_with($to, '/') || str_starts_with($to, '//')) {
        $to = '/compte';
    }
    redirect($to);
}

function auth_logout(): void
{
    logout_user();
    redirect('/connexion');
}

function auth_forgot_form(): void
{
    view('auth/forgot', ['title' => 'Mot de passe oublié | EliteTap'], 'auth');
}

/** Crée un lien de réinitialisation valable 1 h (ou 72 h pour un nouveau compte). */
function make_reset_link(int $userId, int $hours = 1): string
{
    $token = bin2hex(random_bytes(32));
    q('UPDATE users SET reset_token_hash = ?, reset_expires_at = ? WHERE id = ?',
        [hash('sha256', $token), date('Y-m-d H:i:s', time() + $hours * 3600), $userId]);
    return url('/reinitialiser/' . $token);
}

function auth_forgot(): void
{
    $email = strtolower(input('email', 190));
    $key = throttle_key('forgot');
    if (!too_many_attempts($key, 5, 60)) {
        hit_attempt($key);
        $user = row('SELECT id, name FROM users WHERE email = ?', [$email]);
        if ($user) {
            $link = make_reset_link((int) $user['id']);
            send_mail($email, 'Réinitialiser votre mot de passe EliteTap',
                "Bonjour {$user['name']},\n\nPour choisir un nouveau mot de passe, ouvrez ce lien (valable 1 heure) :\n$link\n\n"
                . "Si vous n'avez rien demandé, ignorez ce message.\n\nL'équipe EliteTap\n");
        }
    }
    // Même message dans tous les cas : ne révèle pas si le compte existe
    flash('success', 'Si un compte existe pour ce courriel, un lien de réinitialisation vient d\'être envoyé.');
    redirect('/mot-de-passe-oublie');
}

function find_reset_user(string $token): ?array
{
    $u = row('SELECT id, email FROM users WHERE reset_token_hash = ?', [hash('sha256', $token)]);
    if (!$u) {
        return null;
    }
    $exp = scalar('SELECT reset_expires_at FROM users WHERE id = ?', [$u['id']]);
    return ($exp && strtotime($exp) > time()) ? $u : null;
}

function auth_reset_form(string $token): void
{
    $u = find_reset_user($token);
    view('auth/reset', ['title' => 'Nouveau mot de passe | EliteTap', 'token' => $token, 'valid' => $u !== null, 'email' => $u['email'] ?? ''], 'auth');
}

function auth_reset(string $token): void
{
    $u = find_reset_user($token);
    if (!$u) {
        flash('error', 'Ce lien a expiré. Demandez-en un nouveau.');
        redirect('/mot-de-passe-oublie');
    }
    $pw = $_POST['password'] ?? '';
    $pw2 = $_POST['password_confirm'] ?? '';
    if (!is_string($pw) || mb_strlen($pw) < 10) {
        flash('error', 'Le mot de passe doit contenir au moins 10 caractères.');
        redirect('/reinitialiser/' . $token);
    }
    if ($pw !== $pw2) {
        flash('error', 'Les deux mots de passe ne correspondent pas.');
        redirect('/reinitialiser/' . $token);
    }
    q('UPDATE users SET password_hash = ?, reset_token_hash = NULL, reset_expires_at = NULL WHERE id = ?',
        [password_hash($pw, PASSWORD_DEFAULT), $u['id']]);
    login_user((int) $u['id']);
    flash('success', 'Mot de passe enregistré. Bienvenue !');
    redirect('/compte');
}
