<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/require-admin.php';

$db = getDb();
$posts = $db->query('SELECT id, title, slug, category, status, published_at, updated_at FROM posts ORDER BY updated_at DESC')->fetchAll();
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Posts | Admin do blog — <?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="robots" content="noindex, nofollow" />
  <link rel="stylesheet" href="/blog.css" />
</head>
<body class="admin-body">
  <header class="admin-header">
    <div class="container admin-header-inner">
      <strong>Admin do blog</strong>
      <nav>
        <a href="/admin/leads.php">Leads do formulário</a>
        <a href="/admin/backup.php">Backup</a>
        <a href="/blog/">Ver blog</a>
        <a href="/admin/logout.php">Sair</a>
      </nav>
    </div>
  </header>

  <main class="container admin-main">
    <div class="admin-main-head">
      <h1>Posts</h1>
      <a href="/admin/post-form.php" class="btn-admin">+ Novo post</a>
    </div>

    <?php if (empty($posts)): ?>
      <p class="admin-empty">Nenhum post ainda. Clique em "Novo post" pra criar o primeiro.</p>
    <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr>
            <th>Título</th>
            <th>Categoria</th>
            <th>Status</th>
            <th>Atualizado</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($posts as $post): ?>
            <tr>
              <td><?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars(POST_CATEGORIES[$post['category']] ?? (string) $post['category'], ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <span class="admin-status admin-status-<?= htmlspecialchars($post['status'], ENT_QUOTES, 'UTF-8') ?>">
                  <?= $post['status'] === 'published' ? 'Publicado' : 'Rascunho' ?>
                </span>
              </td>
              <td><?= htmlspecialchars(blogDateBR($post['updated_at']), ENT_QUOTES, 'UTF-8') ?></td>
              <td class="admin-table-actions">
                <a href="/admin/post-form.php?id=<?= (int) $post['id'] ?>">Editar</a>
                <form method="post" action="/admin/post-delete.php" onsubmit="return confirm('Excluir este post?');">
                  <input type="hidden" name="id" value="<?= (int) $post['id'] ?>" />
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>" />
                  <button type="submit" class="admin-link-danger">Excluir</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </main>
</body>
</html>
