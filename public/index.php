<?php
declare(strict_types=1);

// Serveur de développement PHP : laisser passer les fichiers statiques
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

// Sur cPanel : si le dossier app/ n'est pas à côté de public_html, ajuster ce chemin.
require __DIR__ . '/../app/bootstrap.php';
require APP_DIR . '/routes.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$path = $path === '/' ? '/' : rtrim($path, '/');

dispatch($method, $path);
