<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/mailer.php';

header('Content-Type: application/json; charset=utf-8');

function respond(bool $success, string $message, int $status = 200): never
{
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respond(false, 'Método não permitido.', 405);
}

$nome       = trim((string) ($_POST['nome'] ?? ''));
$telefone   = trim((string) ($_POST['telefone'] ?? ''));
$email      = trim((string) ($_POST['email'] ?? ''));
$tipoEvento = trim((string) ($_POST['tipo_evento'] ?? ''));
$mensagem   = trim((string) ($_POST['mensagem'] ?? ''));
$pagina     = trim((string) ($_POST['pagina'] ?? ''));

// Honeypot: campo invisível no form (via CSS), só bot preenche. Responde
// sucesso pra não dar sinal de que foi filtrado, mas descarta.
if (trim((string) ($_POST['website'] ?? '')) !== '') {
    respond(true, 'Recebido.');
}

if ($nome === '' || $telefone === '' || $email === '' || $tipoEvento === '' || $mensagem === '') {
    respond(false, 'Preencha todos os campos: nome, telefone, e-mail, tipo de evento e mensagem.', 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'E-mail inválido.', 422);
}

// Grava o lead no banco ANTES de qualquer integração externa. Garante que a
// solicitação nunca se perca (mesmo com a ActiveCampaign fora do ar) e que dê
// pra ver todas as submissões no admin (/admin/leads.php). Falha de banco não
// derruba o restante — só registra no log.
$leadId = null;
try {
    $db = getDb();
    $stmt = $db->prepare(
        'INSERT INTO leads (nome, telefone, email, tipo_evento, mensagem, pagina)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$nome, $telefone, $email, $tipoEvento, $mensagem, $pagina]);
    $leadId = (int) $db->lastInsertId();
} catch (Throwable $e) {
    error_log('[subscribe] Falha ao gravar lead no banco: ' . $e->getMessage());
}

// Confirma o envio pro visitante ANTES de SMTP/ActiveCampaign. Se a API
// travar, o Render devolve 502 no gateway — mesmo com o lead já gravado.
// flush + Connection: close deixa o browser receber o 200; timeouts curtos
// abaixo evitam o script estourar o limite do proxy.
$okMessage = 'Recebemos sua solicitação. Em breve um consultor entra em contato.';
respondAndContinue($okMessage);

try {
    sendLeadNotification([
        'nome'        => $nome,
        'telefone'    => $telefone,
        'email'       => $email,
        'tipo_evento' => $tipoEvento,
        'mensagem'    => $mensagem,
        'pagina'      => $pagina,
    ]);
} catch (Throwable $e) {
    error_log('[subscribe] Falha ao notificar lead por e-mail: ' . $e->getMessage());
}

$acApiUrl          = acBaseUrl(acEnv('ACTIVECAMPAIGN_API_URL', ACTIVE_CAMPAIGN_API_URL));
$acApiKey          = acEnv('ACTIVECAMPAIGN_API_KEY', ACTIVE_CAMPAIGN_API_KEY);
$acTagId           = acEnv('ACTIVECAMPAIGN_TAG_ID', ACTIVE_CAMPAIGN_TAG_ID);
$acListId          = acEnv('ACTIVECAMPAIGN_LIST_ID', ACTIVE_CAMPAIGN_LIST_ID);
$acFieldEventType  = acEnv('ACTIVECAMPAIGN_FIELD_EVENT_TYPE', ACTIVE_CAMPAIGN_FIELD_EVENT_TYPE);
$acFieldMessage    = acEnv('ACTIVECAMPAIGN_FIELD_MESSAGE', ACTIVE_CAMPAIGN_FIELD_MESSAGE);
$acFieldPage       = acEnv('ACTIVECAMPAIGN_FIELD_PAGE', ACTIVE_CAMPAIGN_FIELD_PAGE);

$nameParts = preg_split('/\s+/', $nome, 2) ?: [$nome];
$firstName = $nameParts[0];
$lastName  = $nameParts[1] ?? '';

$fieldValues = [];
if ($acFieldEventType !== '') {
    $fieldValues[] = ['field' => $acFieldEventType, 'value' => $tipoEvento];
}
if ($acFieldMessage !== '' && $mensagem !== '') {
    $fieldValues[] = ['field' => $acFieldMessage, 'value' => $mensagem];
}
if ($acFieldPage !== '' && $pagina !== '') {
    $fieldValues[] = ['field' => $acFieldPage, 'value' => $pagina];
}

$contactPayload = [
    'contact' => array_filter([
        'email'       => $email,
        'firstName'   => $firstName,
        'lastName'    => $lastName,
        'phone'       => $telefone,
        'fieldValues' => $fieldValues !== [] ? $fieldValues : null,
    ], static fn ($value): bool => $value !== null),
];

if ($acApiUrl === '' || $acApiKey === '') {
    error_log('[subscribe] ActiveCampaign não configurado (ACTIVECAMPAIGN_API_URL/KEY ausentes). '
        . 'Payload que seria enviado: ' . json_encode($contactPayload, JSON_UNESCAPED_UNICODE));
    exit;
}

$syncResult = acRequest($acApiUrl, $acApiKey, '/api/3/contact/sync', $contactPayload);

if (!$syncResult['ok'] && $fieldValues !== [] && str_starts_with($syncResult['error'], 'HTTP ')) {
    // Campo customizado inválido (ID errado no env) não pode impedir o cadastro.
    // Não retenta em timeout — isso é o que virava 502 no Render.
    unset($contactPayload['contact']['fieldValues']);
    $syncResult = acRequest($acApiUrl, $acApiKey, '/api/3/contact/sync', $contactPayload);
}

if (!$syncResult['ok']) {
    error_log('[subscribe] Falha ao sincronizar contato na ActiveCampaign: ' . $syncResult['error']);
    exit;
}

// Marca o lead como sincronizado com a ActiveCampaign (só pra referência no
// admin — não afeta a resposta ao visitante).
if ($leadId !== null) {
    try {
        getDb()->prepare('UPDATE leads SET enviado_ac = 1 WHERE id = ?')->execute([$leadId]);
    } catch (Throwable $e) {
        error_log('[subscribe] Falha ao marcar lead como enviado à AC: ' . $e->getMessage());
    }
}

$contactId = $syncResult['data']['contact']['id'] ?? null;

// Passo 2: aplica a tag "Treme Terra - Lead Site" (ID 14) para disparar
// automações da conta. Falha aqui não derruba o cadastro — o contato já
// existe no passo 1; o erro fica só no log.
if ($contactId !== null && $acTagId !== '') {
    $tagResult = acRequest($acApiUrl, $acApiKey, '/api/3/contactTags', [
        'contactTag' => [
            'contact' => (string) $contactId,
            'tag'     => (string) $acTagId,
        ],
    ]);
    if (!$tagResult['ok']) {
        error_log('[subscribe] Falha ao aplicar tag ActiveCampaign (id ' . $acTagId . '): ' . $tagResult['error']);
    }
}

if ($contactId !== null && $acListId !== '') {
    acRequest($acApiUrl, $acApiKey, '/api/3/contactLists', [
        'contactList' => [
            'list'    => (int) $acListId,
            'contact' => (int) $contactId,
            'status'  => 1, // 1 = subscribed
        ],
    ]);
}

exit;

function respondAndContinue(string $message): void
{
    $json = json_encode(['success' => true, 'message' => $message], JSON_UNESCAPED_UNICODE);
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    header('Connection: close');
    header('Content-Length: ' . (string) strlen($json));
    echo $json;
    ignore_user_abort(true);
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
        return;
    }
    while (ob_get_level() > 0) {
        ob_end_flush();
    }
    flush();
}

function acEnv(string $name, string $fallback = ''): string
{
    foreach ([getenv($name), $_ENV[$name] ?? null, $_SERVER[$name] ?? null] as $value) {
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }
    }
    return $fallback;
}

/** Aceita URL com ou sem /api/3 no final (erro comum no painel do Render). */
function acBaseUrl(string $url): string
{
    $url = rtrim($url, '/');
    return (string) preg_replace('#/api/3$#', '', $url);
}

function acRequest(string $apiUrl, string $apiKey, string $path, array $payload): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'data' => [], 'error' => 'extensão curl ausente no PHP'];
    }

    $ch = curl_init(rtrim($apiUrl, '/') . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Api-Token: ' . $apiKey,
        ],
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT        => 8,
    ]);
    $response   = curl_exec($ch);
    $httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError  = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpStatus < 200 || $httpStatus >= 300) {
        return ['ok' => false, 'data' => [], 'error' => $curlError !== '' ? $curlError : ('HTTP ' . $httpStatus . ': ' . (string) $response)];
    }

    $decoded = json_decode((string) $response, true);
    return ['ok' => true, 'data' => is_array($decoded) ? $decoded : [], 'error' => ''];
}
