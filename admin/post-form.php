<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/require-admin.php';

$db = getDb();
$post = null;
$faqItems = [];

if (isset($_GET['id'])) {
    $stmt = $db->prepare('SELECT * FROM posts WHERE id = ?');
    $stmt->execute([(int) $_GET['id']]);
    $post = $stmt->fetch();
    if ($post === false) {
        http_response_code(404);
        exit('Post não encontrado.');
    }
    if (!empty($post['faq_json'])) {
        $decoded = json_decode((string) $post['faq_json'], true);
        if (is_array($decoded)) {
            $faqItems = $decoded;
        }
    }
}

$isEdit = $post !== null;
$pageHeading = $isEdit ? 'Editar post' : 'Novo post';
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($pageHeading, ENT_QUOTES, 'UTF-8') ?> | Admin do blog</title>
  <meta name="robots" content="noindex, nofollow" />
  <link rel="stylesheet" href="/blog.css" />
  <link href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css" rel="stylesheet" />
</head>
<body class="admin-body">
  <header class="admin-header">
    <div class="container admin-header-inner">
      <strong>Admin do blog</strong>
      <nav>
        <a href="/admin/index.php">Voltar aos posts</a>
        <a href="/admin/logout.php">Sair</a>
      </nav>
    </div>
  </header>

  <main class="container admin-main">
    <h1><?= htmlspecialchars($pageHeading, ENT_QUOTES, 'UTF-8') ?></h1>

    <form method="post" action="/admin/post-save.php" id="post-form" class="admin-form">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>" />
      <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int) $post['id'] ?>" />
      <?php endif; ?>

      <label>Título (H1)
        <input type="text" name="title" id="field-title" required value="<?= htmlspecialchars((string) ($post['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
      </label>

      <label>URL (slug)
        <input type="text" name="slug" id="field-slug" required value="<?= htmlspecialchars((string) ($post['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
      </label>

      <label>Categoria
        <select name="category">
          <option value="">Selecione</option>
          <?php foreach (POST_CATEGORIES as $value => $label): ?>
            <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= ($post['category'] ?? '') === $value ? 'selected' : '' ?>>
              <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>Title tag (SEO, até 60 caracteres — sem precisar incluir "Treme Terra", isso é adicionado automaticamente)
        <input type="text" name="seo_title" maxlength="70" value="<?= htmlspecialchars((string) ($post['seo_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
      </label>

      <label>Meta description (SEO, até 160 caracteres)
        <textarea name="seo_description" rows="2" maxlength="180"><?= htmlspecialchars((string) ($post['seo_description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
      </label>

      <label>Resposta direta (primeiras linhas do post — regra GEO)
        <textarea name="direct_answer" rows="2"><?= htmlspecialchars((string) ($post['direct_answer'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
      </label>

      <label>URL da imagem de capa
        <input type="url" name="cover_image_url" value="<?= htmlspecialchars((string) ($post['cover_image_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
      </label>

      <label>Texto alternativo da imagem (alt)
        <input type="text" name="cover_image_alt" value="<?= htmlspecialchars((string) ($post['cover_image_alt'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
      </label>

      <label>Conteúdo do post</label>
      <div id="quill-editor"><?= $post['body_html'] ?? '' ?></div>
      <input type="hidden" name="body_html" id="field-body-html" />

      <fieldset class="admin-faq-fieldset">
        <legend>FAQ (perguntas frequentes)</legend>
        <div id="faq-list">
          <?php foreach ($faqItems as $item): ?>
            <div class="admin-faq-row">
              <input type="text" name="faq_question[]" placeholder="Pergunta" value="<?= htmlspecialchars((string) ($item['question'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
              <textarea name="faq_answer[]" placeholder="Resposta" rows="2"><?= htmlspecialchars((string) ($item['answer'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
              <button type="button" class="admin-link-danger" onclick="this.closest('.admin-faq-row').remove()">Remover</button>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn-admin-ghost" id="add-faq">+ Adicionar pergunta</button>
      </fieldset>

      <label>Status
        <select name="status">
          <option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Rascunho</option>
          <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publicado</option>
        </select>
      </label>

      <button type="submit" class="btn-admin">Salvar</button>
    </form>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
  <script>
    const quill = new Quill('#quill-editor', { theme: 'snow' });

    const titleField = document.getElementById('field-title');
    const slugField = document.getElementById('field-slug');
    let slugManuallyEdited = <?= $isEdit ? 'true' : 'false' ?>;

    slugField.addEventListener('input', () => { slugManuallyEdited = true; });

    titleField.addEventListener('input', () => {
      if (slugManuallyEdited) return;
      slugField.value = titleField.value
        .toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
    });

    document.getElementById('add-faq').addEventListener('click', () => {
      const row = document.createElement('div');
      row.className = 'admin-faq-row';
      row.innerHTML = `
        <input type="text" name="faq_question[]" placeholder="Pergunta" />
        <textarea name="faq_answer[]" placeholder="Resposta" rows="2"></textarea>
        <button type="button" class="admin-link-danger" onclick="this.closest('.admin-faq-row').remove()">Remover</button>
      `;
      document.getElementById('faq-list').appendChild(row);
    });

    document.getElementById('post-form').addEventListener('submit', () => {
      document.getElementById('field-body-html').value = quill.root.innerHTML;
    });
  </script>
</body>
</html>
