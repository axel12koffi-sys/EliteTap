<?php
declare(strict_types=1);

/*
 * Envoi des courriels.
 *  - Par SMTP (recommandé) si 'mail' => ['smtp' => [...]] est rempli dans config.php :
 *    authentifié avec une vraie boîte (ex. info@elitetap.ca créée dans cPanel), donc bien
 *    mieux accepté par Gmail / Outlook que mail().
 *  - Sinon par mail() de PHP.
 *  - 'log_only' => true : rien n'est envoyé, tout est écrit dans storage/mail.log (tests).
 * Chaque envoi est noté dans la table mail_log (destinataire, sujet, résultat ; jamais le contenu).
 */

function mail_transport(): string
{
    $m = config('mail', []);
    if (!empty($m['log_only'])) {
        return 'log';
    }
    return !empty($m['smtp']['host']) ? 'smtp' : 'mail';
}

function send_mail(string $to, string $subject, string $body, ?string $replyTo = null): bool
{
    $to = trim(str_replace(["\r", "\n"], '', $to));
    $subject = str_replace(["\r", "\n"], ' ', $subject);
    if (!valid_email($to)) {
        mail_log($to, $subject, 'failed', mail_transport(), 'Adresse invalide');
        return false;
    }
    $transport = mail_transport();
    $error = null;

    if ($transport === 'log') {
        file_put_contents(APP_ROOT . '/storage/mail.log',
            "---- " . now() . "\nTo: $to\nSubject: $subject\n\n$body\n", FILE_APPEND);
        $ok = true;
    } elseif ($transport === 'smtp') {
        try {
            smtp_send($to, $subject, $body, $replyTo);
            $ok = true;
        } catch (Throwable $e) {
            $ok = false;
            $error = $e->getMessage();
            error_log('SMTP : ' . $error);
        }
    } else {
        $from = config('mail')['from'];
        $headers = mail_headers($to, $replyTo, false);
        $ok = @mail($to, mb_encode_mimeheader($subject, 'UTF-8', 'B'), quoted_printable_encode(crlf($body)),
            implode("\r\n", $headers), '-f' . $from);
        if (!$ok) {
            $error = 'mail() a refusé l\'envoi (vérifier la configuration du serveur)';
        }
    }
    mail_log($to, $subject, $ok ? ($transport === 'log' ? 'logged' : 'sent') : 'failed', $transport, $error);
    return $ok;
}

function crlf(string $s): string
{
    return preg_replace("/\r\n|\r|\n/", "\r\n", $s);
}

function mail_headers(string $to, ?string $replyTo, bool $withAddressing): array
{
    $from = config('mail')['from'];
    $name = config('mail')['from_name'] ?? 'EliteTap';
    $domain = substr(strrchr($from, '@') ?: '@localhost', 1);
    $h = [];
    if ($withAddressing) {
        $h[] = 'Date: ' . date(DATE_RFC2822);
        $h[] = 'To: <' . $to . '>';
    }
    $h[] = 'From: ' . mb_encode_mimeheader($name, 'UTF-8', 'B') . ' <' . $from . '>';
    if ($replyTo && valid_email($replyTo)) {
        $h[] = 'Reply-To: <' . $replyTo . '>';
    }
    $h[] = 'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $domain . '>';
    $h[] = 'MIME-Version: 1.0';
    $h[] = 'Content-Type: text/plain; charset=UTF-8';
    $h[] = 'Content-Transfer-Encoding: quoted-printable';
    return $h;
}

/** Client SMTP minimal : SSL (465), STARTTLS (587) ou sans chiffrement (tests). */
function smtp_send(string $to, string $subject, string $body, ?string $replyTo): void
{
    $c = config('mail')['smtp'];
    $host = $c['host'];
    $port = (int) ($c['port'] ?? 465);
    $secure = $c['secure'] ?? ($port === 465 ? 'ssl' : 'tls');
    $timeout = (int) ($c['timeout'] ?? 15);
    $ctx = stream_context_create(['ssl' => ['peer_name' => $host, 'SNI_enabled' => true]]);
    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $fp = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        throw new RuntimeException("Connexion impossible à $host:$port ($errstr)");
    }
    stream_set_timeout($fp, $timeout);

    $read = function () use ($fp): array {
        $data = '';
        while (($line = fgets($fp, 1024)) !== false) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        return [(int) substr($data, 0, 3), trim($data)];
    };
    // $label : ce qui apparaît dans un message d'erreur (jamais l'identifiant ni le mot de passe)
    $cmd = function (string $line, array $expect, ?string $label = null) use ($fp, $read): string {
        fwrite($fp, $line . "\r\n");
        [$code, $text] = $read();
        if (!in_array($code, $expect, true)) {
            throw new RuntimeException('Le serveur SMTP a refusé ' . ($label ?? $line) . ' : ' . $text);
        }
        return $text;
    };

    try {
        [$code, $text] = $read();
        if ($code !== 220) {
            throw new RuntimeException("Accueil SMTP inattendu : $text");
        }
        $helo = parse_url((string) config('app_url'), PHP_URL_HOST) ?: 'localhost';
        $ehlo = $cmd("EHLO $helo", [250]);
        if ($secure === 'tls') {
            $cmd('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Impossible d\'activer TLS');
            }
            $ehlo = $cmd("EHLO $helo", [250]);
        }
        if (!empty($c['user'])) {
            $cmd('AUTH LOGIN', [334]);
            $cmd(base64_encode((string) $c['user']), [334], 'l\'identifiant');
            $cmd(base64_encode((string) $c['pass']), [235], 'le mot de passe (vérifier user/pass)');
        }
        $from = config('mail')['from'];
        $cmd("MAIL FROM:<$from>", [250]);
        $cmd("RCPT TO:<$to>", [250, 251]);
        $cmd('DATA', [354]);
        $headers = mail_headers($to, $replyTo, true);
        $headers[] = 'Subject: ' . mb_encode_mimeheader($subject, 'UTF-8', 'B');
        $content = implode("\r\n", $headers) . "\r\n\r\n" . quoted_printable_encode(crlf($body));
        // « Dot-stuffing » : une ligne qui commence par un point est doublée
        $content = preg_replace('/^\./m', '..', $content);
        $cmd($content . "\r\n.", [250], 'le message');
        @fwrite($fp, "QUIT\r\n");
    } finally {
        fclose($fp);
    }
}

function mail_log(string $to, string $subject, string $status, string $transport, ?string $error): void
{
    try {
        q('INSERT INTO mail_log (to_email, subject, status, transport, error, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [mb_substr($to, 0, 190), mb_substr($subject, 0, 255), $status, $transport, $error ? mb_substr($error, 0, 500) : null, now()]);
        if (random_int(1, 100) === 1) {
            q('DELETE FROM mail_log WHERE created_at < ?', [date('Y-m-d H:i:s', time() - 90 * 86400)]);
        }
    } catch (Throwable $e) {
        // Table absente (migration 004 non exécutée) : on n'empêche pas l'envoi
    }
}
