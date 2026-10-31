<?php
// Crée (ou promeut) un compte administrateur.
// Usage : php bin/create-admin.php courriel@exemple.ca "Nom complet"
// Le mot de passe est demandé au terminal (ou généré si vide).
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    exit("CLI uniquement.\n");
}
require __DIR__ . '/../app/bootstrap.php';

[$_, $email, $name] = $argv + [null, null, null];
if (!$email || !valid_email($email) || !$name) {
    exit("Usage : php bin/create-admin.php courriel@exemple.ca \"Nom complet\"\n");
}
$email = strtolower($email);

echo 'Mot de passe (10 caractères min., vide = généré) : ';
$pw = trim((string) fgets(STDIN));
if ($pw === '') {
    $pw = random_code(14);
    echo "Mot de passe généré : $pw\n";
} elseif (mb_strlen($pw) < 10) {
    exit("Trop court.\n");
}

$existing = row('SELECT id FROM users WHERE email = ?', [$email]);
if ($existing) {
    q("UPDATE users SET role = 'admin', password_hash = ?, name = ? WHERE id = ?", [password_hash($pw, PASSWORD_DEFAULT), $name, $existing['id']]);
    echo "Compte existant promu administrateur.\n";
} else {
    q("INSERT INTO users (email, password_hash, name, role, created_at) VALUES (?, ?, ?, 'admin', ?)",
        [$email, password_hash($pw, PASSWORD_DEFAULT), $name, now()]);
    echo "Administrateur créé.\n";
}
echo "Connexion : " . url('/connexion') . "\n";
