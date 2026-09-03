<?php
declare(strict_types=1);

// Carrega um arquivo .env local (se existir) para as variáveis lidas via
// getenv() (ActiveCampaign em subscribe.php, admin do blog etc.). NÃO
// sobrescreve variáveis já definidas no ambiente do servidor — então em
// produção o painel do Render continua sendo a fonte da verdade e o .env
// é só conveniência pra desenvolvimento local. O .env real nunca é
// commitado (está no .gitignore); use .env.example como modelo.
(static function (): void {
    // dist/.env (quando servido do build) ou .env na raiz do repo (fonte).
    foreach ([__DIR__ . '/.env', dirname(__DIR__) . '/.env'] as $envPath) {
        if (!is_file($envPath) || !is_readable($envPath)) {
            continue;
        }
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $eq = strpos($line, '=');
            if ($eq === false) {
                continue;
            }
            $name  = trim(substr($line, 0, $eq));
            $value = trim(substr($line, $eq + 1));
            // Remove aspas envolventes, se houver.
            if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[-1] === $value[0]) {
                $value = substr($value, 1, -1);
            }
            // Variável já definida no ambiente real vence — não sobrescreve.
            if ($name === '' || getenv($name) !== false) {
                continue;
            }
            putenv("$name=$value");
            $_ENV[$name]    = $value;
            $_SERVER[$name] = $value;
        }
        break; // usa o primeiro .env encontrado
    }
})();

// Fuso horário da aplicação. O servidor (Render) roda em UTC, então sem isto
// todo date()/hora aparecia 3h adiantada do horário de São Paulo. Os
// timestamps continuam GRAVADOS em UTC no banco (gmdate/CURRENT_TIMESTAMP);
// a conversão pra hora local é só na exibição, via os helpers abaixo.
const SITE_TIMEZONE = 'America/Sao_Paulo';
date_default_timezone_set(SITE_TIMEZONE);

/**
 * Converte um timestamp gravado em UTC ("Y-m-d H:i:s") para ISO 8601 com o
 * fuso de São Paulo (ex.: 2026-04-22T09:31:24-03:00) — formato válido pro
 * schema.org (datePublished/dateModified). Devolve o valor original se não
 * conseguir interpretar.
 */
function blogDateIso(?string $utc): string
{
    $utc = trim((string) $utc);
    if ($utc === '') {
        return '';
    }
    try {
        $dt = new DateTime($utc, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone(SITE_TIMEZONE));
        return $dt->format('c');
    } catch (Throwable) {
        return $utc;
    }
}

/**
 * Converte um timestamp gravado em UTC para exibição legível em horário de
 * São Paulo (padrão dd/mm/aaaa HH:MM). Usado no admin.
 */
function blogDateBR(?string $utc, string $format = 'd/m/Y H:i'): string
{
    $utc = trim((string) $utc);
    if ($utc === '') {
        return '';
    }
    try {
        $dt = new DateTime($utc, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone(SITE_TIMEZONE));
        return $dt->format($format);
    } catch (Throwable) {
        return $utc;
    }
}

const SITE_NAME        = 'Treme Terra Audiovisual';
const SITE_TITLE       = 'Treme Terra Audiovisual | Som, iluminação e produção para eventos';
const SITE_DESCRIPTION = 'A Treme Terra Audiovisual entrega sonorização, iluminação, '
    . 'projeção e estrutura completa de palco para eventos — orçamento transparente, atendimento '
    . 'rápido e solução sob medida pro seu evento.';
const SITE_URL         = 'https://www.tremeterraaudiovisual.com.br'; // TODO: confirmar domínio final do redesign
const SITE_LOCALE      = 'pt_BR';
const SITE_THEME_COLOR = '#080c16';

const CONTACT_PHONE_DISPLAY = '(11) 98478-1889';
const CONTACT_PHONE_TEL     = '+5511984781889';
const CONTACT_EMAIL         = 'comercial@tremeterraaudiovisual.com.br';
const CONTACT_HOURS         = 'Segunda a sexta, 9h às 18h';
const CONTACT_CITY          = 'São Paulo'; // TODO: confirmar cidade/endereço completo com o cliente
const CONTACT_REGION        = 'SP';
const CONTACT_COUNTRY       = 'BR';

const SOCIAL_LINKS = [
    'instagram' => '#', // TODO: link real do Instagram
    'facebook'  => '#', // TODO: link real do Facebook
    'whatsapp'  => 'https://wa.me/5511984781889',
];

// Ano de fundação real (2011) — usado no schema Organization abaixo. Nunca
// trocar por um cálculo "X anos atrás" que não bate com o ano real.
const FOUNDING_YEAR = 2011;

// Nome do produto flagship. Centralizado aqui pra nunca precisar caçar a
// string em duas linguagens (HTML estático + schema PHP) se mudar de novo.
const FLAGSHIP_PRODUCT_NAME = 'Innova Show';

// Integração ActiveCampaign (usada por subscribe.php) — todo valor real
// deve vir de variável de ambiente do servidor (getenv), NUNCA commitado
// em texto no repositório. As constantes abaixo são só o fallback vazio
// para ambiente local sem as env vars configuradas — nesse caso
// subscribe.php cai em "modo desenvolvimento" (loga o payload, não
// inventa um sucesso real da API).
//
// Onde achar cada valor no painel ActiveCampaign:
//   URL/Chave  -> Settings > Developer
//   Lista      -> Lists > (a lista) > o ID aparece na URL
//   Campos     -> Settings > Manage Fields > o ID aparece ao editar o campo
const ACTIVE_CAMPAIGN_API_URL          = ''; // TODO: ex. https://SUACONTA.api-us1.com
const ACTIVE_CAMPAIGN_API_KEY          = ''; // TODO: token real — usar getenv('ACTIVECAMPAIGN_API_KEY') em produção
const ACTIVE_CAMPAIGN_LIST_ID          = ''; // TODO: ID numérico da lista de destino
const ACTIVE_CAMPAIGN_FIELD_EVENT_TYPE = ''; // TODO: ID do campo customizado "Tipo de evento"
const ACTIVE_CAMPAIGN_FIELD_MESSAGE    = ''; // TODO: ID do campo customizado "Mensagem"
const ACTIVE_CAMPAIGN_FIELD_PAGE       = ''; // TODO: ID do campo customizado "Página de origem"
