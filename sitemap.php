<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';

// URLs estáveis das páginas estáticas do site (mesma lista de canonicalPath
// já usada em scripts/generate-php.mjs). Não muda a cada build — só quando
// uma página nova for criada, atualizar aqui também.
$staticPaths = [
    '/',
    '/locacao-equipamentos',
    '/produtora-de-eventos-corporativos',
    '/dj-para-eventos',
    '/aluguel-som-profissional',
    '/iluminacao-para-festas',
    '/painel-de-led',
    '/locacao-painel-led',
    '/dj-para-casamentos',
    '/dj-para-formatura',
    '/casamentos',
    '/formaturas',
    '/empresa-audiovisual',
    '/shows',
    '/streaming-para-eventos-corporativos',
    '/blog',
];

$deployDate = gmdate('Y-m-d');

$urls = [];
foreach ($staticPaths as $path) {
    $urls[] = ['loc' => SITE_URL . $path, 'lastmod' => $deployDate];
}

$db = getDb();
$posts = $db->query("SELECT slug, updated_at FROM posts WHERE status = 'published' ORDER BY published_at DESC")->fetchAll();
foreach ($posts as $post) {
    $lastmod = $post['updated_at'] ? substr((string) $post['updated_at'], 0, 10) : $deployDate;
    $urls[] = ['loc' => SITE_URL . '/blog/' . $post['slug'], 'lastmod' => $lastmod];
}

header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $url): ?>
  <url>
    <loc><?= htmlspecialchars($url['loc'], ENT_QUOTES | ENT_XML1, 'UTF-8') ?></loc>
    <lastmod><?= htmlspecialchars($url['lastmod'], ENT_QUOTES | ENT_XML1, 'UTF-8') ?></lastmod>
  </url>
<?php endforeach; ?>
</urlset>
