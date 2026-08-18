<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Página não encontrada | <?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="robots" content="noindex, follow" />
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

  <section class="blog-hero" style="text-align:center;">
    <div class="container">
      <p class="eyebrow">Erro 404</p>
      <h1>Essa página não existe (ou mudou de lugar)</h1>
      <p>Confira o endereço digitado, ou volte pra Home / pro blog pra continuar navegando.</p>
    </div>
  </section>

  <div class="container" style="padding-block: 3rem; display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
    <a href="/" class="btn-admin">Ir para a Home</a>
    <a href="/blog" class="btn-admin-ghost">Ver o blog</a>
  </div>

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
