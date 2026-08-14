// Pós-build: transforma cada dist/<page>.html num dist/<page>.php que gera as
// meta tags e o Schema.org via PHP (config.php + partials/seo-meta.php),
// mantendo intactos os assets já versionados pelo Vite (hash no nome do
// arquivo). Rodar depois de `vite build` — ver script "build:php".
import { readFileSync, writeFileSync, mkdirSync, copyFileSync, readdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const distDir = path.join(root, 'dist');

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
            ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Sonorização para Eventos']],
            ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Consultoria e Staff']],
            ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Projeção']],
            ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Painel de LED']],
        ],
    ],
]`,
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
];

// Mapa <page>.html -> <page>.php pra reescrever links internos entre
// páginas depois que cada uma virar PHP (o <body> é copiado como está, e o
// HTML fonte referencia as outras páginas pelo nome .html gerado pelo Vite).
const htmlToPhp = new Map(pages.map((p) => [`/${p.htmlFile}`, `/${p.phpFile}`]));
htmlToPhp.set('/index.html', '/'); // home usa raiz, não /index.php, nos links internos

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

require __DIR__ . '/partials/seo-meta.php';
?>
  ${fontBlock}
  ${assetTags}`;

  let newHtml = html.replace(/<head>[\s\S]*?<\/head>/, `<head>\n${newHead}\n</head>`);

  // Reescreve links internos .html -> .php (ou raiz, no caso da home) agora
  // que a página vizinha também vira PHP.
  for (const [from, to] of htmlToPhp) {
    newHtml = newHtml.split(`"${from}"`).join(`"${to}"`);
  }

  writeFileSync(distPhpPath, newHtml, 'utf-8');
}

mkdirSync(path.join(distDir, 'partials'), { recursive: true });
copyFileSync(path.join(root, 'config.php'), path.join(distDir, 'config.php'));
copyFileSync(path.join(root, 'partials', 'seo-meta.php'), path.join(distDir, 'partials', 'seo-meta.php'));
copyFileSync(path.join(root, 'subscribe.php'), path.join(distDir, 'subscribe.php'));

console.log(`dist/{${pages.map((p) => p.phpFile).join(',')}} gerados + config.php/partials/subscribe.php copiados para dist/.`);
