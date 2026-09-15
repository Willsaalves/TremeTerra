<?php
/**
 * Testa o fluxo ActiveCampaign do site Treme Terra:
 *   1) POST /api/3/contact/sync
 *   2) POST /api/3/contactTags  (tag 14 = "Treme Terra - Lead Site")
 *
 * Uso (na raiz do repo):
 *   php scripts/test-activecampaign.php
 *   php scripts/test-activecampaign.php voce@email.com "Nome Teste" 11900000000 Corporativos
 *
 * Precisa de ACTIVECAMPAIGN_API_KEY no .env (ou no ambiente).
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

function envVal(string $name, string $fallback = ''): string
{
    foreach ([getenv($name), $_ENV[$name] ?? null, $_SERVER[$name] ?? null] as $value) {
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }
    }
    return $fallback;
}

function baseUrl(string $url): string
{
    $url = rtrim($url, '/');
    return (string) preg_replace('#/api/3$#', '', $url);
}

function maskKey(string $key): string
{
    $len = strlen($key);
    if ($len < 8) {
        return '(chave curta demais)';
    }
    return substr($key, 0, 6) . '…' . substr($key, -4) . " ({$len} chars)";
}

function acCall(string $apiUrl, string $apiKey, string $method, string $path, ?array $payload = null, bool $verifySsl = true): array
{
    $ch = curl_init(rtrim($apiUrl, '/') . $path);
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Api-Token: ' . $apiKey,
    ];
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => $verifySsl,
        CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
    ];
    $ca = envVal('SSL_CERT_FILE');
    if ($ca === '') {
        $ca = (string) (ini_get('curl.cainfo') ?: ini_get('openssl.cafile') ?: '');
    }
    if ($verifySsl && $ca !== '' && is_file($ca)) {
        $opts[CURLOPT_CAINFO] = $ca;
    }
    if ($payload !== null) {
        $opts[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    curl_setopt_array($ch, $opts);
    $body   = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errno  = curl_errno($ch);
    $error  = curl_error($ch);
    curl_close($ch);

    if ($verifySsl && $errno === 60) {
        fwrite(STDERR, "aviso: PHP local sem certificado SSL; repetindo a chamada sem verificação (só neste script de teste)." . PHP_EOL);
        return acCall($apiUrl, $apiKey, $method, $path, $payload, false);
    }

    $decoded = json_decode((string) $body, true);
    return [
        'ok'     => $body !== false && $status >= 200 && $status < 300,
        'status' => $status,
        'error'  => $error,
        'data'   => is_array($decoded) ? $decoded : ['raw' => $body],
    ];
}

function line(string $msg): void
{
    fwrite(STDOUT, $msg . PHP_EOL);
}

function fail(string $msg, int $code = 1): never
{
    fwrite(STDERR, "ERRO: {$msg}" . PHP_EOL);
    exit($code);
}

if (!function_exists('curl_init')) {
    fail('extensão curl do PHP não está disponível');
}

$apiUrl = baseUrl(envVal('ACTIVECAMPAIGN_API_URL', defined('ACTIVE_CAMPAIGN_API_URL') ? ACTIVE_CAMPAIGN_API_URL : ''));
$apiKey = envVal('ACTIVECAMPAIGN_API_KEY');
if ($apiKey === '') {
    $apiKey = envVal('ACTIVE_CAMPAIGN_API_TOKEN');
}
if ($apiKey === '' && defined('ACTIVE_CAMPAIGN_API_KEY')) {
    $apiKey = ACTIVE_CAMPAIGN_API_KEY;
}
$tagId    = envVal('ACTIVECAMPAIGN_TAG_ID', defined('ACTIVE_CAMPAIGN_TAG_ID') ? ACTIVE_CAMPAIGN_TAG_ID : '14');
$field    = envVal('ACTIVECAMPAIGN_FIELD_EVENT_TYPE', defined('ACTIVE_CAMPAIGN_FIELD_EVENT_TYPE') ? ACTIVE_CAMPAIGN_FIELD_EVENT_TYPE : '5');
$fieldMsg = envVal('ACTIVECAMPAIGN_FIELD_MESSAGE', defined('ACTIVE_CAMPAIGN_FIELD_MESSAGE') ? ACTIVE_CAMPAIGN_FIELD_MESSAGE : '6');

$email    = $argv[1] ?? ('teste-tremeterra+' . date('YmdHis') . '@example.com');
$nome     = $argv[2] ?? 'Teste Treme Terra';
$telefone = $argv[3] ?? '11900000000';
$evento   = $argv[4] ?? 'Corporativos';

if ($apiUrl === '' || $apiKey === '') {
    fail("ACTIVECAMPAIGN_API_KEY vazia. Crie um .env na raiz (veja .env.example) ou exporte a variável.\n"
        . "Exemplo:\n  ACTIVECAMPAIGN_API_URL=https://jessicaallparty.api-us1.com\n  ACTIVECAMPAIGN_API_KEY=seu-token");
}

line('=== Teste ActiveCampaign — Treme Terra ===');
line('URL:  ' . $apiUrl);
line('Key:  ' . maskKey($apiKey));
line('Tag:  ' . $tagId);
line('Campo tipo evento: ' . $field);
line('Campo mensagem: ' . $fieldMsg);
line('Email de teste: ' . $email);
line('');

line('0) Conferindo a tag ' . $tagId . '…');
$tagCheck = acCall($apiUrl, $apiKey, 'GET', '/api/3/tags/' . rawurlencode($tagId));
if (!$tagCheck['ok']) {
    $detail = $tagCheck['error'] !== '' ? $tagCheck['error'] : json_encode($tagCheck['data'], JSON_UNESCAPED_UNICODE);
    fail('não achei a tag ' . $tagId . ' (HTTP ' . $tagCheck['status'] . '). ' . $detail);
}
$tagName = $tagCheck['data']['tag']['tag'] ?? '(sem nome)';
line('   OK — "' . $tagName . '"');
line('');

$phoneDigits = preg_replace('/\D+/', '', $telefone) ?? '';
$payload = [
    'contact' => [
        'email'     => $email,
        'firstName' => $nome,
        'phone'     => $phoneDigits,
        'fieldValues' => [
            ['field' => (string) $field, 'value' => $evento],
            ['field' => (string) $fieldMsg, 'value' => 'Treme Terra — Tipo: ' . $evento],
        ],
    ],
];

line('1) POST /api/3/contact/sync …');
$sync = acCall($apiUrl, $apiKey, 'POST', '/api/3/contact/sync', $payload);
line('   HTTP ' . $sync['status']);
if (!$sync['ok']) {
    fail('sync falhou: ' . ($sync['error'] !== '' ? $sync['error'] : json_encode($sync['data'], JSON_UNESCAPED_UNICODE)));
}
$contactId = $sync['data']['contact']['id'] ?? null;
if ($contactId === null || $contactId === '') {
    fail('sync ok, mas a resposta não trouxe contact.id: ' . json_encode($sync['data'], JSON_UNESCAPED_UNICODE));
}
line('   contato id = ' . $contactId);
line('');

line('2) POST /api/3/contactTags (tag ' . $tagId . ') …');
$tag = acCall($apiUrl, $apiKey, 'POST', '/api/3/contactTags', [
    'contactTag' => [
        'contact' => (string) $contactId,
        'tag'     => (string) $tagId,
    ],
]);
line('   HTTP ' . $tag['status']);
if (!$tag['ok']) {
    $msg = $tag['error'] !== '' ? $tag['error'] : json_encode($tag['data'], JSON_UNESCAPED_UNICODE);
    // 422 = tag já aplicada nesse contato — ainda conta como sucesso do fluxo.
    if ($tag['status'] === 422) {
        line('   aviso: tag já estava no contato (HTTP 422). Fluxo ok.');
    } else {
        fail('aplicar tag falhou: ' . $msg);
    }
} else {
    $contactTagId = $tag['data']['contactTag']['id'] ?? '?';
    line('   contactTag id = ' . $contactTagId);
}

line('');
line('SUCESSO. Confira na ActiveCampaign o contato ' . $email . ' com a tag "' . $tagName . '".');
exit(0);
