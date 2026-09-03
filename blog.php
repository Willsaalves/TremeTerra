<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';

$db = getDb();
$posts = $db->query("SELECT title, slug, category, seo_description, direct_answer, cover_image_url, cover_image_alt, published_at FROM posts WHERE status = 'published' ORDER BY published_at DESC")->fetchAll();

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
  <?php // ?v= com o mtime do arquivo: quebra o cache do navegador a cada
        // mudança no CSS (blog.css não tem hash no nome como os assets do Vite,
        // e é servido com cache de 30 dias). Sem isso, mudanças no visual do
        // blog não chegam a quem já visitou. ?>
  <?php $blogCssV = @filemtime(__DIR__ . '/blog.css') ?: 1; ?>
  <link rel="stylesheet" href="/blog.css?v=<?= $blogCssV ?>" />
  <noscript><style>.post-card{opacity:1 !important;transform:none !important;}</style></noscript>
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
      <p class="blog-intro">
        Conteúdo pra te ajudar a planejar seu evento em São Paulo, direto de quem monta e opera todo
        dia: aqui você encontra dicas, tendências e guias práticos sobre produção audiovisual para
        eventos corporativos, casamentos, formaturas e shows — de aluguel de som profissional a
        projetos de iluminação cênica e painéis de LED, para garantir que seu evento tenha o impacto
        visual e sonoro que ele merece.
      </p>
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
        <?php foreach ($posts as $i => $post): ?>
          <?php
            $catLabel = (!empty($post['category']) && isset(POST_CATEGORIES[$post['category']]))
                ? POST_CATEGORIES[$post['category']] : '';
            $excerpt  = (string) ($post['seo_description'] ?: $post['direct_answer']);
            $cover    = trim((string) ($post['cover_image_url'] ?? ''));
            $coverAlt = (string) ($post['cover_image_alt'] ?: $post['title']);
            $featured = $i === 0; // primeiro post em destaque
          ?>
          <a href="/blog/<?= htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8') ?>"
             class="post-card<?= $featured ? ' post-card--featured' : '' ?>"
             data-category="<?= htmlspecialchars((string) $post['category'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="post-card-cover">
              <?php if ($cover !== ''): ?>
                <img src="<?= htmlspecialchars($cover, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($coverAlt, ENT_QUOTES, 'UTF-8') ?>" loading="lazy" />
              <?php else: ?>
                <span class="post-card-cover-fallback" aria-hidden="true">
                  <?= htmlspecialchars($catLabel !== '' ? $catLabel : SITE_NAME, ENT_QUOTES, 'UTF-8') ?>
                </span>
              <?php endif; ?>
              <?php if ($catLabel !== ''): ?>
                <span class="category"><?= htmlspecialchars($catLabel, ENT_QUOTES, 'UTF-8') ?></span>
              <?php endif; ?>
            </div>
            <div class="post-card-body">
              <?php if (!empty($post['published_at'])): ?>
                <time class="post-card-date" datetime="<?= htmlspecialchars(blogDateIso($post['published_at']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(blogDateBR($post['published_at'], 'd/m/Y'), ENT_QUOTES, 'UTF-8') ?></time>
              <?php endif; ?>
              <h2><?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?></h2>
              <?php if ($excerpt !== ''): ?>
                <p><?= htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8') ?></p>
              <?php endif; ?>
              <span class="post-card-more">Ler mais
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
              </span>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <section class="blog-cta">
      <h2>Precisa de suporte técnico para o seu próximo evento?</h2>
      <p>
        A <?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?> oferece soluções completas em
        sonorização, iluminação, painel de LED e transmissão ao vivo. Fale com a nossa equipe e
        solicite um orçamento sob medida para o seu evento em São Paulo.
      </p>
      <a href="/#contato" class="blog-cta-btn">Solicitar orçamento</a>
    </section>
  </div>

  <footer class="blog-footer">
    <div class="container">
      <p>
        © <?= date('Y') ?> <?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?> — Grupo All Party ·
        <a href="/#contato">Solicitar orçamento</a>
      </p>
    </div>
  </footer>

  <script src="/blog.js?v=<?= @filemtime(__DIR__ . '/blog.js') ?: 1 ?>" defer></script>
</body>
</html>
