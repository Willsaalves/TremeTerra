<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';

// Índice de sitemaps (/sitemap.xml). Não lista URLs diretamente — aponta pros
// sitemaps filhos, mantendo o blog APARTADO das páginas:
//   /pages-sitemap.xml      -> páginas institucionais/de serviço
//   /blog/page-sitemap.xml  -> blog (índice + posts publicados)
// O robots.txt aponta pra este índice, e o Google descobre os dois a partir
// daqui — sem duplicar os posts entre os arquivos.
$deployDate = gmdate('Y-m-d');

// lastmod do blog = data do post mais recente atualizado (se houver).
$blogLastmod = $deployDate;
try {
    $db = getDb();
    $row = $db->query("SELECT MAX(updated_at) AS m FROM posts WHERE status = 'published'")->fetch();
    if (!empty($row['m'])) {
        $blogLastmod = substr((string) $row['m'], 0, 10);
    }
} catch (Throwable $e) {
    // banco indisponível: mantém a data do deploy
}

$sitemaps = [
    ['loc' => SITE_URL . '/pages-sitemap.xml',     'lastmod' => $deployDate],
    ['loc' => SITE_URL . '/blog/page-sitemap.xml', 'lastmod' => $blogLastmod],
];

header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($sitemaps as $sm): ?>
  <sitemap>
    <loc><?= htmlspecialchars($sm['loc'], ENT_QUOTES | ENT_XML1, 'UTF-8') ?></loc>
    <lastmod><?= htmlspecialchars($sm['lastmod'], ENT_QUOTES | ENT_XML1, 'UTF-8') ?></lastmod>
  </sitemap>
<?php endforeach; ?>
</sitemapindex>
