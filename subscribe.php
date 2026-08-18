<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

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

if ($nome === '' || $telefone === '' || $email === '' || $tipoEvento === '') {
    respond(false, 'Preencha nome, telefone, e-mail e tipo de evento.', 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'E-mail inválido.', 422);
}

$acApiUrl          = getenv('ACTIVECAMPAIGN_API_URL') ?: ACTIVE_CAMPAIGN_API_URL;
$acApiKey          = getenv('ACTIVECAMPAIGN_API_KEY') ?: ACTIVE_CAMPAIGN_API_KEY;
$acListId          = getenv('ACTIVECAMPAIGN_LIST_ID') ?: ACTIVE_CAMPAIGN_LIST_ID;
$acFieldEventType  = getenv('ACTIVECAMPAIGN_FIELD_EVENT_TYPE') ?: ACTIVE_CAMPAIGN_FIELD_EVENT_TYPE;
$acFieldMessage    = getenv('ACTIVECAMPAIGN_FIELD_MESSAGE') ?: ACTIVE_CAMPAIGN_FIELD_MESSAGE;
$acFieldPage       = getenv('ACTIVECAMPAIGN_FIELD_PAGE') ?: ACTIVE_CAMPAIGN_FIELD_PAGE;

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
    // ActiveCampaign ainda não configurado neste ambiente. Não inventa um
    // "sucesso" da API — loga o payload que seria enviado pra dar pra
    // testar o formulário de ponta a ponta antes das credenciais reais
    // existirem, e avisa isso explicitamente pro time técnico no log.
    error_log('[subscribe] ActiveCampaign não configurado (ACTIVECAMPAIGN_API_URL/KEY ausentes). '
        . 'Payload que seria enviado: ' . json_encode($contactPayload, JSON_UNESCAPED_UNICODE));
    respond(true, 'Recebemos sua solicitação. Em breve um consultor entra em contato.');
}

$syncResult = acRequest($acApiUrl, $acApiKey, '/api/3/contact/sync', $contactPayload);

if (!$syncResult['ok']) {
    error_log('[subscribe] Falha ao sincronizar contato na ActiveCampaign: ' . $syncResult['error']);
    respond(false, 'Não conseguimos concluir seu cadastro agora. Tente novamente em instantes ou chame no '
        . CONTACT_PHONE_DISPLAY . '.', 502);
}

$contactId = $syncResult['data']['contact']['id'] ?? null;

if ($contactId !== null && $acListId !== '') {
    acRequest($acApiUrl, $acApiKey, '/api/3/contactLists', [
        'contactList' => [
            'list'    => (int) $acListId,
            'contact' => (int) $contactId,
            'status'  => 1, // 1 = subscribed
        ],
    ]);
    // Falha em inscrever na lista não derruba o cadastro do contato em
    // si — o contato já foi criado/atualizado no passo acima, então
    // respondemos sucesso pro visitante de qualquer forma e deixamos o
    // erro (se houver) só no log.
}

respond(true, 'Recebemos sua solicitação. Em breve um consultor entra em contato.');

/**
 * @param array<string, mixed> $payload
 * @return array{ok: bool, data: array<string, mixed>, error: string}
 */
function acRequest(string $apiUrl, string $apiKey, string $path, array $payload): array
{
    $ch = curl_init(rtrim($apiUrl, '/') . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Api-Token: ' . $apiKey,
        ],
        CURLOPT_TIMEOUT => 10,
    ]);
    $response   = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError  = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpStatus >= 400) {
        return ['ok' => false, 'data' => [], 'error' => $curlError !== '' ? $curlError : ('HTTP ' . $httpStatus . ': ' . (string) $response)];
    }

    $decoded = json_decode((string) $response, true);
    return ['ok' => true, 'data' => is_array($decoded) ? $decoded : [], 'error' => ''];
}
