// Pós-build: transforma cada dist/<page>.html num dist/<page>.php que gera as
// meta tags e o Schema.org via PHP (config.php + partials/seo-meta.php),
// mantendo intactos os assets já versionados pelo Vite (hash no nome do
// arquivo). Rodar depois de `vite build` — ver script "build:php".
import { readFileSync, writeFileSync, mkdirSync, copyFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const distDir = path.join(root, 'dist');

const faqPhp = (items) =>
  '[\n' +
  items.map(([q, a]) => `        ['question' => '${phpEscape(q)}', 'answer' => '${phpEscape(a)}'],`).join('\n') +
  '\n    ]';

function phpEscape(str) {
  return str.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}

const pages = [
  {
    htmlFile: 'index.html',
    phpFile: 'index.php',
    pageTitleExpr: 'SITE_TITLE',
    pageDescriptionExpr: 'SITE_DESCRIPTION',
    canonicalPath: '/',
    schemaVar: `[
    '@context'    => 'https://schema.org',
    '@type'       => 'Service',
    '@id'         => SITE_URL . '/#service',
    'name'        => SITE_NAME,
    'serviceType' => 'Sonorização, iluminação e produção de eventos',
    'description' => SITE_DESCRIPTION,
    'provider'    => ['@id' => SITE_URL . '/#organization'],
    'areaServed'  => 'BR',
    'hasOfferCatalog' => [
        '@type' => 'OfferCatalog',
        'name'  => 'Soluções Treme Terra',
        'itemListElement' => [
            ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Som Profissional']],
            ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Iluminação Cênica']],
            ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Painel de LED']],
            ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Produção Completa']],
        ],
    ],
]`,
    faqItems: [
      ['Quais serviços estão inclusos na produção audiovisual?', 'Som profissional, iluminação cênica, painéis de LED, produção técnica completa e operação de DJ para todos os tipos de evento.'],
      ['Como funciona o orçamento?', 'Após o briefing, elaboramos proposta técnica com itens, quantidades e valores. Orçamento gratuito e sem compromisso.'],
      ['Vocês atendem fora de São Paulo?', 'Sim. Atendemos todo o Estado de São Paulo, com foco na Grande SP e interior (até 200 km).'],
      ['Qual a antecedência mínima?', 'Recomendamos 30 dias para eventos grandes. Para eventos menores, dependemos da disponibilidade.'],
      ['Têm backup de equipamentos?', 'Sim. Todos os equipamentos têm redundância. Nossa equipe carrega extras para cada evento.'],
      ['Trabalham com eventos híbridos?', 'Sim. Streaming completo com câmeras, encoding e transmissão para Zoom, Teams e YouTube.'],
    ],
  },
  {
    htmlFile: 'locacao-equipamentos.html',
    phpFile: 'locacao-equipamentos.php',
    pageTitleExpr: "'Locação de Equipamentos para Eventos | ' . SITE_NAME",
    pageDescriptionExpr: "'Locação de iluminação, painel de LED, som e estrutura de palco para o seu evento, com entrega e suporte técnico da ' . SITE_NAME . '.'",
    canonicalPath: '/locacao-equipamentos',
    breadcrumbNameExpr: "'Locação de Equipamentos'",
    schemaVar: `[
    '@context'    => 'https://schema.org',
    '@type'       => 'Service',
    '@id'         => SITE_URL . '/locacao-equipamentos#service',
    'name'        => 'Locação de Equipamentos para Eventos',
    'serviceType' => 'Locação de iluminação, painel de LED, som e estrutura de palco',
    'description' => 'Locação de iluminação, painel de LED, som e estrutura de palco para o seu evento, com entrega e suporte técnico.',
    'provider'    => ['@id' => SITE_URL . '/#organization'],
    'areaServed'  => 'BR',
    'hasOfferCatalog' => [
        '@type' => 'OfferCatalog',
        'name'  => 'Equipamentos para locação',
        'itemListElement' => [
            ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Locação de Iluminação']],
            ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Locação de Painel de LED']],
            ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Locação de Som']],
            ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Locação de Estrutura de Palco']],
        ],
    ],
]`,
  },
  {
    htmlFile: 'produtora-de-eventos-corporativos.html',
    phpFile: 'produtora-de-eventos-corporativos.php',
    pageTitleExpr: "'Eventos Corporativos em SP | ' . SITE_NAME",
    pageDescriptionExpr: "'Treme Terra: produtora de eventos corporativos em SP. Produção 360° com som, iluminação, LED e equipe técnica. 800+ eventos.'",
    canonicalPath: '/produtora-de-eventos-corporativos',
    breadcrumbNameExpr: "'Eventos Corporativos'",
    schemaVar: `[
    '@context'    => 'https://schema.org',
    '@type'       => 'Service',
    '@id'         => SITE_URL . '/produtora-de-eventos-corporativos#service',
    'name'        => 'Eventos Corporativos',
    'serviceType' => 'Produção audiovisual para eventos corporativos',
    'description' => 'Produção 360° com som, iluminação, painéis de LED e equipe técnica para eventos corporativos em São Paulo.',
    'provider'    => ['@id' => SITE_URL . '/#organization'],
    'areaServed'  => 'BR',
]`,
    faqItems: [
      ['Como solicitar orçamento?', 'Preencha o formulário ou acione via WhatsApp. Proposta em até 48h após briefing.'],
      ['O que está incluso?', 'Som, iluminação, LED, palco, equipe, transporte, montagem e desmontagem.'],
      ['Fazem produção para eventos híbridos?', 'Sim. Streaming com câmeras, encoding e transmissão.'],
      ['Produzem com antecedência mínima?', 'Com 45–60 dias para eventos grandes; 30 dias para eventos menores.'],
      ['Produzem para todos os portes?', 'Sim, de startups até grandes corporações.'],
      ['Quais são as referências da Treme Terra Audiovisual?', 'Cases documentados com CPS, ATLAS LIVE e outros.'],
    ],
  },
  {
    htmlFile: 'dj-para-eventos.html',
    phpFile: 'dj-para-eventos.php',
    pageTitleExpr: "'DJ para Eventos em SP | ' . SITE_NAME",
    pageDescriptionExpr: "'DJ para eventos corporativos, aniversários e festas em SP. Repertório personalizado, equipamento incluso.'",
    canonicalPath: '/dj-para-eventos',
    breadcrumbNameExpr: "'DJ para Eventos'",
    schemaVar: `[
    '@context'    => 'https://schema.org',
    '@type'       => 'Service',
    '@id'         => SITE_URL . '/dj-para-eventos#service',
    'name'        => 'DJ para Eventos',
    'serviceType' => 'DJ profissional para eventos',
    'description' => 'DJ para eventos corporativos, aniversários, casamentos e festas em São Paulo, com repertório personalizado e equipamento incluso.',
    'provider'    => ['@id' => SITE_URL . '/#organization'],
    'areaServed'  => 'BR',
]`,
    faqItems: [
      ['A Treme Terra Audiovisual tem repertório personalizado?', 'Sim. Definido em reunião prévia conforme público e tema.'],
      ['Os equipamentos são incluídos?', 'Sim. Som profissional, mesa e cabos. Integramos ao existente.'],
      ['O DJ pode atuar em eventos corporativos?', 'Sim. Podem ser em eventos de networking, confraternização, lançamento de produto etc.'],
      ['Consigo contratar DJ para casamentos?', 'Sim. O DJ para eventos pode atuar em cerimônia, jantar e festa.'],
      ['Qual a duração mínima?', '4 horas. Pacotes 6h, 8h, 12h com desconto.'],
      ['Possuem opção com iluminação?', 'Sim. Pacote DJ + iluminação com Moving Heads e LED.'],
    ],
  },
  {
    htmlFile: 'aluguel-som-profissional.html',
    phpFile: 'aluguel-som-profissional.php',
    pageTitleExpr: "'Aluguel de Som Profissional para Eventos | ' . SITE_NAME",
    pageDescriptionExpr: "'Aluguel de som profissional com Line Array, caixas PA, subs, microfones e mesas para eventos em SP. Equipe técnica incluída.'",
    canonicalPath: '/aluguel-som-profissional',
    breadcrumbNameExpr: "'Aluguel de Som'",
    schemaVar: `[
    '@context'    => 'https://schema.org',
    '@type'       => 'Service',
    '@id'         => SITE_URL . '/aluguel-som-profissional#service',
    'name'        => 'Aluguel de Som Profissional',
    'serviceType' => 'Locação de sistemas de som para eventos',
    'description' => 'Aluguel de som profissional com Line Array, caixas PA, subwoofers, mesas digitais e microfones para eventos em São Paulo.',
    'provider'    => ['@id' => SITE_URL . '/#organization'],
    'areaServed'  => 'BR',
]`,
    faqItems: [
      ['Frete e montagem estão inclusos?', 'Sim. O orçamento inclui transporte, montagem, operação e desmontagem. Sem custos ocultos.'],
      ['Qual a antecedência mínima?', 'O ideal é que seja com antecedência mínima de 30 dias. Para Line Array, 45 dias.'],
      ['Fornecem backup?', 'Sim. Equipamentos extras no local.'],
      ['Som para cerimônia ao ar livre?', 'Sim. Sistemas wireless e portáteis sem necessidade de energia no local.'],
      ['O que é sistema PA?', 'PA (Public Address) é o sistema de som profissional — caixas, amplificadores, mixadores e microfones.'],
      ['Alugam para eventos corporativos?', 'Sim. Som para palestras, lançamentos, convenções e treinamentos em SP.'],
    ],
  },
  {
    htmlFile: 'iluminacao-para-festas.html',
    phpFile: 'iluminacao-para-festas.php',
    pageTitleExpr: "'Iluminação para Festas e Eventos | ' . SITE_NAME",
    pageDescriptionExpr: "'Iluminação cênica para casamentos, formaturas e corporativos em SP. Moving heads, ribaltas, strobes e DMX.'",
    canonicalPath: '/iluminacao-para-festas',
    breadcrumbNameExpr: "'Iluminação'",
    schemaVar: `[
    '@context'    => 'https://schema.org',
    '@type'       => 'Service',
    '@id'         => SITE_URL . '/iluminacao-para-festas#service',
    'name'        => 'Iluminação para Festas e Eventos',
    'serviceType' => 'Iluminação cênica para eventos',
    'description' => 'Iluminação cênica com Moving Heads, ribaltas, strobes e programação DMX para casamentos, formaturas e eventos corporativos em São Paulo.',
    'provider'    => ['@id' => SITE_URL . '/#organization'],
    'areaServed'  => 'BR',
]`,
    faqItems: [
      ['Criam as cenas?', 'Sim. Cenas personalizadas para cada momento — cerimônia, jantar, baile e show.'],
      ['Combina com som e LED?', 'Sim. Integração completa: iluminação, som e painéis de LED sincronizados.'],
      ['Funciona ao ar livre?', 'Sim. Equipamentos IP65 para eventos externos, mesmo sob chuva.'],
      ['Precisa de muita energia?', 'Equipamentos LED eficientes. Evento médio: circuito 20A suficiente.'],
      ['Iluminação para cerimônia?', 'Sim. Suave e romântica para cerimônias, com opções natural e artificial.'],
      ['Programação DMX personalizada?', 'Sim. Cores, intensidade, timing e efeitos conforme a identidade do evento.'],
    ],
  },
  {
    htmlFile: 'painel-de-led.html',
    phpFile: 'painel-de-led.php',
    pageTitleExpr: "'Painel de LED para Eventos em SP | ' . SITE_NAME",
    pageDescriptionExpr: "'Painel de LED para eventos: telões P2, P3, P5 indoor/outdoor, 4K, para shows, casamentos e corporativos em SP.'",
    canonicalPath: '/painel-de-led',
    breadcrumbNameExpr: "'Painel de LED'",
    schemaVar: `[
    '@context'    => 'https://schema.org',
    '@type'       => 'Service',
    '@id'         => SITE_URL . '/painel-de-led#service',
    'name'        => 'Painel de LED para Eventos',
    'serviceType' => 'Locação de painéis de LED para eventos',
    'description' => 'Painéis de LED P2, P3, P5 e 4K, indoor e outdoor, para shows, casamentos, formaturas e eventos corporativos em São Paulo.',
    'provider'    => ['@id' => SITE_URL . '/#organization'],
    'areaServed'  => 'BR',
]`,
    productSchemaVar: `[
    '@context'   => 'https://schema.org',
    '@type'      => 'Product',
    'name'       => 'Painel de LED para Eventos',
    'description' => 'Painéis de LED modulares P2, P3, P5 e 4K para locação em eventos, indoor e outdoor.',
    'brand'      => ['@type' => 'Brand', 'name' => SITE_NAME],
    'additionalProperty' => [
        ['@type' => 'PropertyValue', 'name' => 'P2', 'value' => 'Indoor premium, 2–8 m'],
        ['@type' => 'PropertyValue', 'name' => 'P3', 'value' => 'Versátil, 3–15 m'],
        ['@type' => 'PropertyValue', 'name' => 'P5', 'value' => 'Outdoor, 5–30 m'],
        ['@type' => 'PropertyValue', 'name' => '4K', 'value' => 'Ultra HD, sob medida'],
    ],
    'offers' => [
        '@type'         => 'Offer',
        'priceCurrency' => 'BRL',
        'availability'  => 'https://schema.org/InStock',
        'url'           => SITE_URL . '/painel-de-led',
    ],
]`,
    faqItems: [
      ['Qual tamanho preciso do painel de led para eventos?', 'Depende da distância. Até 10m: P2/P3. 10–20m: P3/P5. +20m: P5.'],
      ['Funciona ao ar livre?', 'Sim. IP65 contra chuva e poeira.'],
      ['A instalação do painel de led é inclusa?', 'Sim. Transporte, montagem, operação e desmontagem.'],
      ['Alugam ou vendem os painéis de led?', 'Fazemos a locação completa com operação técnica.'],
      ['Resolução ideal do painel de led?', 'Indoor: P2/P3. Outdoor: P5/4K. Projetamos o ideal.'],
      ['Mostra imagens e vídeos no painel de led?', 'Sim. HDMI, notebook ou câmera conectados ao painel em tempo real.'],
    ],
  },
  {
    htmlFile: 'locacao-painel-led.html',
    phpFile: 'locacao-painel-led.php',
    pageTitleExpr: "'Locação de Painel de LED em SP | ' . SITE_NAME",
    pageDescriptionExpr: "'Locação de painel de LED com operação técnica: telões P2, P3, P5, 4K para eventos em SP. Orçamento gratuito.'",
    canonicalPath: '/locacao-painel-led',
    breadcrumbNameExpr: "'Locação de LED'",
    schemaVar: `[
    '@context'    => 'https://schema.org',
    '@type'       => 'Service',
    '@id'         => SITE_URL . '/locacao-painel-led#service',
    'name'        => 'Locação de Painel de LED',
    'serviceType' => 'Locação de telões de LED com operação técnica',
    'description' => 'Locação de painel de LED P2, P3, P5 e 4K, indoor e outdoor, com estrutura, operador e montagem completa para eventos em São Paulo.',
    'provider'    => ['@id' => SITE_URL . '/#organization'],
    'areaServed'  => 'BR',
]`,
    faqItems: [
      ['Qual o preço da locação?', 'Depende do pixel pitch, tamanho e duração. Orçamento gratuito.'],
      ['Operam o painel de led?', 'Sim, disponibilizamos o operador de vídeo no local durante todo o evento.'],
      ['Energia específica?', 'LED eficiente. 6m² = circuito 32A suficiente.'],
      ['A locação do painel de led é por hora ou dia?', 'Fazemos os orçamentos personalizados a depender dos pacotes por evento.'],
      ['Como funciona a locação de painel de led para local sem estrutura?', 'Levamos gerador e infraestrutura completa.'],
      ['Atendem fora de SP?', 'Sim. Todo o Estado de SP e, mediante acordo, outros estados.'],
    ],
  },
];

// Mapa <page>.html -> URL sem extensão pra reescrever links internos entre
// páginas depois que cada uma virar PHP (o <body> é copiado como está, e o
// HTML fonte referencia as outras páginas pelo nome .html gerado pelo Vite).
// router.php resolve essas URLs sem extensão pro .php correspondente.
const htmlToClean = new Map(pages.map((p) => [`/${p.htmlFile}`, `/${p.htmlFile.replace(/\.html$/, '')}`]));
htmlToClean.set('/index.html', '/'); // home usa raiz, não /index, nos links internos

for (const page of pages) {
  const distHtmlPath = path.join(distDir, page.htmlFile);
  const distPhpPath = path.join(distDir, page.phpFile);

  const html = readFileSync(distHtmlPath, 'utf-8');

  const headMatch = html.match(/<head>([\s\S]*?)<\/head>/);
  if (!headMatch) {
    throw new Error(`Não encontrei <head>...</head> em dist/${page.htmlFile}`);
  }
  const head = headMatch[1];

  // Preserva: preconnect/preload/stylesheet de fonte + as tags de asset do
  // Vite (script type=module e link stylesheet, já com hash no nome).
  const fontBlockMatch = head.match(/<link rel="preconnect"[\s\S]*?<\/noscript>\s*/);
  const fontBlock = fontBlockMatch ? fontBlockMatch[0].trim() : '';

  const assetTags = [...head.matchAll(/<script type="module"[^>]*><\/script>|<link rel="stylesheet"[^>]*>/g)]
    .map((m) => m[0])
    .filter((tag) => !tag.includes('fonts.googleapis.com'))
    .join('\n  ');

  const faqBlock = page.faqItems
    ? `\n$pageFaqItems = ${faqPhp(page.faqItems)};\n`
    : '';

  const productBlock = page.productSchemaVar
    ? `\n$pageProductSchema = ${page.productSchemaVar};\n`
    : '';

  const newHead = `<?php
// Nota: sem declare(strict_types=1) aqui de propósito — este arquivo tem
// HTML (o <!doctype html>/<html>/<head>) antes do primeiro bloco <?php,
// e strict_types só é aceito como a primeiríssima instrução do script.
// config.php e partials/seo-meta.php (require'd abaixo) já declaram
// strict_types por conta própria — a declaração é por arquivo, então
// isso não abre mão de nada lá.
require_once __DIR__ . '/config.php';

$pageTitle          = ${page.pageTitleExpr};
$pageDescription    = ${page.pageDescriptionExpr};
$pageCanonical      = SITE_URL . '${page.canonicalPath}';
$pageBreadcrumbName = ${page.breadcrumbNameExpr ?? 'null'};

$pageServiceSchema = ${page.schemaVar};
${productBlock}${faqBlock}
require __DIR__ . '/partials/seo-meta.php';
?>
  ${fontBlock}
  ${assetTags}`;

  let newHtml = html.replace(/<head>[\s\S]*?<\/head>/, `<head>\n${newHead}\n</head>`);

  // Reescreve links internos .html -> URL sem extensão (ou raiz, no caso da
  // home) agora que a página vizinha também vira PHP servido pelo router.
  for (const [from, to] of htmlToClean) {
    newHtml = newHtml.split(`"${from}"`).join(`"${to}"`);
  }

  writeFileSync(distPhpPath, newHtml, 'utf-8');
}

mkdirSync(path.join(distDir, 'partials'), { recursive: true });
copyFileSync(path.join(root, 'config.php'), path.join(distDir, 'config.php'));
copyFileSync(path.join(root, 'partials', 'seo-meta.php'), path.join(distDir, 'partials', 'seo-meta.php'));
copyFileSync(path.join(root, 'subscribe.php'), path.join(distDir, 'subscribe.php'));
copyFileSync(path.join(root, 'router.php'), path.join(distDir, 'router.php'));

console.log(`dist/{${pages.map((p) => p.phpFile).join(',')}} gerados + config.php/partials/subscribe.php/router.php copiados para dist/.`);
