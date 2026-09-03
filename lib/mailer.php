<?php
declare(strict_types=1);

/**
 * Envio de e-mail via SMTP (cliente mínimo, sem dependências externas).
 *
 * Usado pra notificar a equipe comercial a cada novo lead do formulário.
 * Toda a configuração vem de variáveis de ambiente (no Render: painel
 * Environment; local: .env) — nada de credencial commitada:
 *
 *   SMTP_HOST        host do servidor SMTP (ex.: smtp.valueserver...)
 *   SMTP_PORT        porta (587 STARTTLS — padrão — ou 465 SSL)
 *   SMTP_USER        usuário/e-mail de autenticação
 *   SMTP_PASS        senha
 *   SMTP_SECURE      "tls" (STARTTLS, padrão) ou "ssl" (porta 465)
 *   MAIL_FROM        remetente (padrão = SMTP_USER)
 *   MAIL_FROM_NAME   nome do remetente (padrão = SITE_NAME)
 *   LEAD_NOTIFY_EMAIL destino da notificação (padrão = CONTACT_EMAIL)
 *
 * Sem SMTP_HOST/USER/PASS, não tenta enviar (retorna false e loga) — o lead
 * já fica salvo no banco de qualquer forma.
 */

function leadNotifyEmail(): string
{
    $to = getenv('LEAD_NOTIFY_EMAIL') ?: '';
    return $to !== '' ? $to : (defined('CONTACT_EMAIL') ? CONTACT_EMAIL : '');
}

/**
 * @param array{nome?:string,telefone?:string,email?:string,tipo_evento?:string,mensagem?:string,pagina?:string} $lead
 */
function sendLeadNotification(array $lead): bool
{
    $host = getenv('SMTP_HOST') ?: '';
    $user = getenv('SMTP_USER') ?: '';
    $pass = getenv('SMTP_PASS') ?: '';
    $to   = leadNotifyEmail();

    if ($host === '' || $user === '' || $pass === '' || $to === '') {
        error_log('[mailer] SMTP não configurado (SMTP_HOST/USER/PASS ou destino ausentes) — notificação de lead não enviada.');
        return false;
    }

    $port   = (int) (getenv('SMTP_PORT') ?: 587);
    $secure = strtolower(getenv('SMTP_SECURE') ?: 'tls'); // tls (STARTTLS) | ssl
    $from     = getenv('MAIL_FROM') ?: $user;
    $fromName = getenv('MAIL_FROM_NAME') ?: (defined('SITE_NAME') ? SITE_NAME : 'Site');
    $replyTo  = trim((string) ($lead['email'] ?? ''));

    $nome     = (string) ($lead['nome'] ?? '');
    $subject  = 'Novo orçamento pelo site' . ($nome !== '' ? ' — ' . $nome : '');

    $linhas = [
        'Nova solicitação de orçamento pelo site:',
        '',
        'Nome:          ' . ($lead['nome'] ?? ''),
        'Telefone:      ' . ($lead['telefone'] ?? ''),
        'E-mail:        ' . ($lead['email'] ?? ''),
        'Tipo de evento:' . ' ' . ($lead['tipo_evento'] ?? ''),
        'Página:        ' . ($lead['pagina'] ?? ''),
        '',
        'Mensagem:',
        ((string) ($lead['mensagem'] ?? '') !== '' ? (string) $lead['mensagem'] : '(sem mensagem)'),
        '',
        '— Enviado automaticamente pelo formulário do site.',
    ];
    $body = implode("\r\n", $linhas);

    try {
        return smtpSend($host, $port, $secure, $user, $pass, $from, $fromName, $to, $replyTo, $subject, $body);
    } catch (Throwable $e) {
        error_log('[mailer] Falha ao enviar notificação de lead: ' . $e->getMessage());
        return false;
    }
}

/**
 * Cliente SMTP mínimo (AUTH LOGIN + STARTTLS/SSL). Lança em qualquer erro.
 */
function smtpSend(
    string $host,
    int $port,
    string $secure,
    string $user,
    string $pass,
    string $from,
    string $fromName,
    string $to,
    string $replyTo,
    string $subject,
    string $body
): bool {
    $transport = $secure === 'ssl' ? "ssl://{$host}:{$port}" : "tcp://{$host}:{$port}";
    $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true]]);
    $fp = @stream_socket_client($transport, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
    if ($fp === false) {
        throw new RuntimeException("conexão SMTP falhou ({$errno}): {$errstr}");
    }
    stream_set_timeout($fp, 15);

    $read = static function () use ($fp): array {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            // resposta multilinha: "250-..." continua; "250 ..." termina
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }
        $code = (int) substr($data, 0, 3);
        return [$code, $data];
    };
    $send = static function (string $cmd) use ($fp): void {
        fwrite($fp, $cmd . "\r\n");
    };
    $expect = static function (array $res, int $ok, string $step): void {
        if ($res[0] !== $ok) {
            throw new RuntimeException("SMTP {$step}: esperado {$ok}, recebido " . trim($res[1]));
        }
    };

    $expect($read(), 220, 'saudação');
    $ehloHost = $_SERVER['SERVER_NAME'] ?? 'localhost';
    $send('EHLO ' . $ehloHost);
    $expect($read(), 250, 'EHLO');

    if ($secure === 'tls') {
        $send('STARTTLS');
        $expect($read(), 220, 'STARTTLS');
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('não foi possível iniciar TLS (STARTTLS)');
        }
        $send('EHLO ' . $ehloHost);
        $expect($read(), 250, 'EHLO pós-TLS');
    }

    $send('AUTH LOGIN');
    $expect($read(), 334, 'AUTH LOGIN');
    $send(base64_encode($user));
    $expect($read(), 334, 'usuário');
    $send(base64_encode($pass));
    $expect($read(), 235, 'autenticação');

    $send('MAIL FROM:<' . $from . '>');
    $expect($read(), 250, 'MAIL FROM');
    $send('RCPT TO:<' . $to . '>');
    $res = $read();
    if ($res[0] !== 250 && $res[0] !== 251) {
        throw new RuntimeException('SMTP RCPT TO: ' . trim($res[1]));
    }
    $send('DATA');
    $expect($read(), 354, 'DATA');

    $encSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $encFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
    $headers = [
        'Date: ' . date('r'),
        'From: ' . $encFromName . ' <' . $from . '>',
        'To: <' . $to . '>',
        'Subject: ' . $encSubject,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
    ];
    if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: <' . $replyTo . '>';
    }
    $message = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($body));
    // dot-stuffing: linha só com "." encerraria o DATA
    $message = preg_replace('/^\./m', '..', $message);
    $send($message);
    $send('.');
    $expect($read(), 250, 'envio (fim do DATA)');

    $send('QUIT');
    fclose($fp);
    return true;
}
