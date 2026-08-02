// Pós-build: transforma dist/index.html num dist/index.php que gera as
// meta tags e o Schema.org via PHP (config.php + partials/seo-meta.php),
// mantendo intactos os assets já versionados pelo Vite (hash no nome do
// arquivo). Rodar depois de `vite build` — ver script "build:php".
import { readFileSync, writeFileSync, mkdirSync, copyFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const distDir = path.join(root, 'dist');
const distIndexHtml = path.join(distDir, 'index.html');
const distIndexPhp = path.join(distDir, 'index.php');

const html = readFileSync(distIndexHtml, 'utf-8');

const headMatch = html.match(/<head>([\s\S]*?)<\/head>/);
if (!headMatch) {
  throw new Error('Não encontrei <head>...</head> em dist/index.html');
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

$pageTitle       = SITE_TITLE;
$pageDescription = SITE_DESCRIPTION;
$pageCanonical   = SITE_URL . '/';

$pageServiceSchema = [
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
];

require __DIR__ . '/partials/seo-meta.php';
?>
  ${fontBlock}
  ${assetTags}`;

const newHtml = html.replace(/<head>[\s\S]*?<\/head>/, `<head>\n${newHead}\n</head>`);

writeFileSync(distIndexPhp, newHtml, 'utf-8');

mkdirSync(path.join(distDir, 'partials'), { recursive: true });
copyFileSync(path.join(root, 'config.php'), path.join(distDir, 'config.php'));
copyFileSync(path.join(root, 'partials', 'seo-meta.php'), path.join(distDir, 'partials', 'seo-meta.php'));
copyFileSync(path.join(root, 'subscribe.php'), path.join(distDir, 'subscribe.php'));

console.log('dist/index.php gerado + config.php/partials/subscribe.php copiados para dist/.');
