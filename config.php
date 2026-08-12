<?php
declare(strict_types=1);

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
