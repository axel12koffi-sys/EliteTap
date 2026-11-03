#!/usr/local/bin/php -q
<?php
// Réception des courriels de info@elitetap.ca dans la messagerie de l'admin.
// cPanel › Redirecteurs › Ajouter un redirecteur : info@elitetap.ca → « Diriger vers un programme » :
//     repositories/EliteTap/bin/inbound-mail.php
// Le courriel arrive aussi normalement dans la boîte info@ (webmail).
// Ce script ne doit rien afficher et toujours se terminer sans erreur, sinon l'expéditeur reçoit un message d'échec.
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    exit;
}
ob_start();
$raw = '';
try {
    $raw = (string) stream_get_contents(STDIN);
    require __DIR__ . '/../app/bootstrap.php';
    $result = inbox_receive($raw);
    @file_put_contents(APP_ROOT . '/storage/inbound.log', date('Y-m-d H:i:s') . " $result\n", FILE_APPEND);
} catch (Throwable $e) {
    // Garde une copie pour pouvoir le retraiter : php bin/inbound-mail.php < storage/inbound-failed/xxx.eml
    $dir = dirname(__DIR__) . '/storage/inbound-failed';
    @mkdir($dir, 0750, true);
    @file_put_contents($dir . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.eml', $raw);
    @file_put_contents(dirname(__DIR__) . '/storage/inbound.log',
        date('Y-m-d H:i:s') . ' ERREUR ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ")\n", FILE_APPEND);
}
ob_end_clean();
exit(0);
