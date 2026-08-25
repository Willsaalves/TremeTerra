<?php
declare(strict_types=1);

// Sitemap dedicado do blog, servido em /blog/page-sitemap.xml (o router
// roteia essa URL pra cá). Lista a página do blog + todos os posts
// publicados, gerado dinamicamente a partir do banco. Os mesmos posts
// também aparecem no /sitemap.xml geral — este é um sitemap extra, no
// formato de caminho que algumas ferramentas de SEO esperam.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';

$deployDate = gmdate('Y-m-d');

$urls = [
    ['loc' => SITE_URL . '/blog/', 'lastmod' => $deployDate],
];

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
