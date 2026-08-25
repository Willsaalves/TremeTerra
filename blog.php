<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';

$db = getDb();
$posts = $db->query("SELECT title, slug, category, seo_description, direct_answer FROM posts WHERE status = 'published' ORDER BY published_at DESC")->fetchAll();

$categoriesPresent = [];
foreach ($posts as $post) {
    $cat = $post['category'];
    if ($cat && isset(POST_CATEGORIES[$cat]) && !isset($categoriesPresent[$cat])) {
        $categoriesPresent[$cat] = POST_CATEGORIES[$cat];
    }
}

$pageTitle          = 'Blog | ' . SITE_NAME;
$pageDescription    = 'Guias sobre som, iluminação, painel de LED, DJ e produção de eventos em São Paulo — pela ' . SITE_NAME . '.';
$pageCanonical      = SITE_URL . '/blog/';
$pageBreadcrumbName = 'Blog';
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
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WQJVN5JZ" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
  <header class="blog-header">
    <div class="container">
      <a href="/" class="logo"><img src="/logo-tremeterra.png" alt="<?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?>" width="150" height="21" /></a>
      <nav><a href="/">Voltar ao site</a></nav>
    </div>
  </header>

  <section class="blog-hero">
    <div class="container">
      <p class="eyebrow">Blog <?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?></p>
      <h1>Guias práticos de som, luz e produção de eventos</h1>
      <p>Conteúdo pra te ajudar a planejar seu evento em São Paulo, direto de quem monta e opera todo dia.</p>
    </div>
  </section>

  <div class="container">
    <?php if (!empty($posts) && count($categoriesPresent) > 1): ?>
      <div class="post-list-filters" role="group" aria-label="Filtrar posts por categoria">
        <button type="button" class="filter-chip is-active" data-filter="all">Todos</button>
        <?php foreach ($categoriesPresent as $slug => $label): ?>
          <button type="button" class="filter-chip" data-filter="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="post-list">
      <?php if (empty($posts)): ?>
        <div class="empty-state">
          <strong>Em breve, novos posts por aqui.</strong>
          Estamos preparando conteúdo sobre locação de som, iluminação, painel de LED, DJ e produção de eventos.
        </div>
      <?php else: ?>
        <?php foreach ($posts as $post): ?>
          <a href="/blog/<?= htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8') ?>" class="post-card" data-category="<?= htmlspecialchars((string) $post['category'], ENT_QUOTES, 'UTF-8') ?>">
            <?php if (!empty($post['category']) && isset(POST_CATEGORIES[$post['category']])): ?>
              <span class="category"><?= htmlspecialchars(POST_CATEGORIES[$post['category']], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
            <h2><?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars((string) ($post['seo_description'] ?: $post['direct_answer']), ENT_QUOTES, 'UTF-8') ?></p>
            <span class="post-card-arrow" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </span>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <footer class="blog-footer">
    <div class="container">
      <p>
        © <?= date('Y') ?> <?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?> — Grupo All Party ·
        <a href="/#contato">Solicitar orçamento</a>
      </p>
    </div>
  </footer>

  <script src="/blog.js" defer></script>
</body>
</html>
