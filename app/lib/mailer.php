<?php
declare(strict_types=1);

/**
 * Envoi via mail() de PHP (fonctionne sur l'hébergement cPanel).
 * Pour une meilleure délivrabilité, configurer SPF/DKIM du domaine dans cPanel > Délivrabilité des courriels.
 */
function send_mail(string $to, string $subject, string $body, ?string $replyTo = null): bool
{
    $from = config('mail')['from'];
    $fromName = config('mail')['from_name'];
    $headers = [
        'From: ' . mb_encode_mimeheader($fromName) . ' <' . $from . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];
    if ($replyTo && valid_email($replyTo)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }
    // Empêche l'injection d'en-têtes
    $to = str_replace(["\r", "\n"], '', $to);
    $subject = mb_encode_mimeheader(str_replace(["\r", "\n"], ' ', $subject));

    if (!empty(config('mail')['log_only'])) {
        file_put_contents(APP_ROOT . '/storage/mail.log',
            "---- " . now() . "\nTo: $to\nSubject: $subject\n\n$body\n", FILE_APPEND);
        return true;
    }
    return @mail($to, $subject, $body, implode("\r\n", $headers), '-f' . $from);
}
