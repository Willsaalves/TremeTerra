<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';

$slug = trim((string) ($_GET['slug'] ?? ''), '/');
if ($slug === '') {
    http_response_code(404);
    exit('Post não encontrado.');
}

$db = getDb();
$stmt = $db->prepare("SELECT * FROM posts WHERE slug = ? AND status = 'published'");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if ($post === false) {
    http_response_code(404);
    ?>
    <!doctype html>
    <html lang="pt-BR">
    <head>
      <meta charset="UTF-8" />
      <title>Post não encontrado | Blog <?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?></title>
      <meta name="robots" content="noindex">
      <link rel="stylesheet" href="/blog.css" />
    </head>
    <body>
      <div class="container" style="padding-block:4rem;text-align:center;">
        <h1>Post não encontrado</h1>
        <p><a href="/blog">Voltar pro blog</a></p>
      </div>
    </body>
    </html>
    <?php
    exit;
}

$faqItems = null;
if (!empty($post['faq_json'])) {
    $decoded = json_decode((string) $post['faq_json'], true);
    if (is_array($decoded) && $decoded !== []) {
        $faqItems = $decoded;
    }
}

$pageTitle          = ($post['seo_title'] ?: $post['title']) . ' | ' . SITE_NAME;
$pageDescription    = (string) ($post['seo_description'] ?: $post['direct_answer'] ?: SITE_DESCRIPTION);
$pageCanonical      = SITE_URL . '/blog/' . $post['slug'];
$pageBreadcrumbTrail = [
    ['name' => 'Blog', 'url' => SITE_URL . '/blog'],
    ['name' => $post['title'], 'url' => $pageCanonical],
];
$pageFaqItems = $faqItems;
$pagePostSchema = [
    '@context'         => 'https://schema.org',
    '@type'            => 'BlogPosting',
    '@id'              => $pageCanonical . '#post',
    'headline'         => $post['title'],
    'description'      => $pageDescription,
    'datePublished'    => $post['published_at'],
    'dateModified'     => $post['updated_at'],
    'mainEntityOfPage' => $pageCanonical,
    'author'           => ['@id' => SITE_URL . '/#organization'],
    'publisher'        => ['@id' => SITE_URL . '/#organization'],
];
if (!empty($post['cover_image_url'])) {
    $pagePostSchema['image'] = $post['cover_image_url'];
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <?php require __DIR__ . '/partials/seo-meta.php'; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700;800&family=Montserrat:wght@400;500;600;700;800&display=swap" />
  <link rel="stylesheet" href="/blog.css" />
</head>
<body>
  <header class="blog-header">
    <div class="container">
      <a href="/" class="logo"><img src="/logo-tremeterra.png" alt="<?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?>" width="150" height="21" /></a>
      <nav><a href="/">Voltar ao site</a></nav>
    </div>
  </header>

  <article class="post">
    <div class="container">
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="/">Home</a> / <a href="/blog">Blog</a> / <?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?>
      </nav>

      <p class="eyebrow">Blog <?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?></p>
      <h1><?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?></h1>

      <?php if (!empty($post['direct_answer'])): ?>
        <p class="direct-answer"><?= htmlspecialchars($post['direct_answer'], ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>

      <?php if (!empty($post['cover_image_url'])): ?>
        <div class="cover">
          <img src="<?= htmlspecialchars($post['cover_image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($post['cover_image_alt'] ?: $post['title']), ENT_QUOTES, 'UTF-8') ?>" loading="lazy" />
        </div>
      <?php endif; ?>

      <div class="prose"><?= $post['body_html'] ?? '' ?></div>

      <?php if ($faqItems !== null): ?>
        <section class="faq">
          <h2>Perguntas frequentes</h2>
          <?php foreach ($faqItems as $item): ?>
            <details class="faq-item">
              <summary><?= htmlspecialchars((string) ($item['question'] ?? ''), ENT_QUOTES, 'UTF-8') ?></summary>
              <p><?= htmlspecialchars((string) ($item['answer'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            </details>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>
    </div>
  </article>

  <footer class="blog-footer">
    <div class="container">
      <p>
        © <?= date('Y') ?> <?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?> — Grupo All Party ·
        <a href="/#contato">Solicitar orçamento</a>
      </p>
    </div>
  </footer>
</body>
</html>
