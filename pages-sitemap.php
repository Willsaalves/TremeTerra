<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

// Sitemap só das páginas institucionais/de serviço (sem blog), servido em
// /pages-sitemap.xml. O blog fica apartado em /blog/page-sitemap.xml. Os dois
// são reunidos pelo índice em /sitemap.xml.
$staticPaths = [
    '/',
    '/locacao-equipamentos/',
    '/produtora-de-eventos-corporativos/',
    '/montagem-de-palco/',
    '/dj-para-eventos/',
    '/aluguel-som-profissional/',
    '/iluminacao-para-festas/',
    '/painel-de-led/',
    '/locacao-painel-led/',
    '/dj-para-casamentos/',
    '/dj-para-formatura/',
    '/casamentos/',
    '/formaturas/',
    '/empresa-audiovisual/',
    '/shows/',
    '/streaming-para-eventos-corporativos/',
];

$deployDate = gmdate('Y-m-d');

header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($staticPaths as $path): ?>
  <url>
    <loc><?= htmlspecialchars(SITE_URL . $path, ENT_QUOTES | ENT_XML1, 'UTF-8') ?></loc>
    <lastmod><?= htmlspecialchars($deployDate, ENT_QUOTES | ENT_XML1, 'UTF-8') ?></lastmod>
  </url>
<?php endforeach; ?>
</urlset>
