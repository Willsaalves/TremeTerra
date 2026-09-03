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
  <link rel="stylesheet" href="/blog.css?v=<?= @filemtime(__DIR__ . "/../blog.css") ?: 1 ?>" />
  <link href="/vendor/quill/quill.snow.css" rel="stylesheet" />
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

    <form method="post" action="/admin/post-save.php" id="post-form" class="admin-form" enctype="multipart/form-data">
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

      <label>Imagem de capa (upload — JPG, PNG, WebP ou GIF, até 5 MB)
        <input type="file" name="cover_image_file" accept="image/jpeg,image/png,image/webp,image/gif" />
      </label>

      <?php if (!empty($post['cover_image_url'])): ?>
        <p class="admin-cover-preview">
          Capa atual:<br />
          <img src="<?= htmlspecialchars((string) $post['cover_image_url'], ENT_QUOTES, 'UTF-8') ?>" alt="Pré-visualização da capa" />
        </p>
      <?php endif; ?>

      <label>ou cole a URL da imagem de capa
        <?php /* type="text" (não "url"): imagens enviadas pelo admin ficam com
           caminho relativo (/uploads/xxx.jpg), que o type="url" rejeitava e
           travava o salvamento. O servidor aceita URL absoluta ou relativa. */ ?>
        <input type="text" name="cover_image_url" value="<?= htmlspecialchars((string) ($post['cover_image_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
      </label>

      <label>Texto alternativo da imagem (alt)
        <input type="text" name="cover_image_alt" value="<?= htmlspecialchars((string) ($post['cover_image_alt'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
      </label>

      <label>Conteúdo do post</label>
      <div class="editor-tools">
        <button type="button" class="btn-admin-ghost" id="insert-table">+ Inserir tabela</button>
      </div>
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

  <!-- Modal de inserir tabela -->
  <div id="table-modal" class="table-modal" hidden>
    <div class="table-modal-card">
      <h2>Inserir tabela</h2>
      <div class="table-modal-controls">
        <label>Colunas
          <input type="number" id="tbl-cols" min="2" max="6" value="3" />
        </label>
        <label>Linhas de dados
          <input type="number" id="tbl-rows" min="1" max="20" value="4" />
        </label>
        <button type="button" class="btn-admin-ghost" id="tbl-build">Gerar campos</button>
        <button type="button" class="btn-admin-ghost" id="tbl-preset">Modelo comparativo</button>
      </div>
      <p class="table-modal-hint">A primeira linha é o cabeçalho. Preencha as células e clique em “Inserir no post”.</p>
      <div id="tbl-grid" class="table-grid"></div>
      <div class="table-modal-actions">
        <button type="button" class="btn-admin-ghost" id="tbl-cancel">Cancelar</button>
        <button type="button" class="btn-admin" id="tbl-insert">Inserir no post</button>
      </div>
    </div>
  </div>

  <script src="/vendor/quill/quill.min.js"></script>
  <script>
    const csrfToken = <?= json_encode(csrfToken(), JSON_UNESCAPED_SLASHES) ?>;

    // Faz upload de um arquivo de imagem pro servidor e insere a <img>
    // apontando pra URL /uploads/... devolvida — em vez de embutir a imagem
    // em base64 (padrão do Quill), que incha o HTML e estoura o limite de
    // POST ao salvar.
    async function uploadImageFile(file) {
      if (!file) return;
      const fd = new FormData();
      fd.append('image', file);
      fd.append('csrf_token', csrfToken);
      try {
        const res = await fetch('/admin/upload.php', { method: 'POST', body: fd });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.url) {
          alert(data.error || 'Falha no upload da imagem.');
          return;
        }
        const range = quill.getSelection(true) || { index: quill.getLength() };
        quill.insertEmbed(range.index, 'image', data.url, 'user');
        quill.setSelection(range.index + 1);
      } catch (e) {
        alert('Erro de rede no upload da imagem.');
      }
    }

    // Botão de imagem da barra de ferramentas.
    function uploadEditorImage() {
      const input = document.createElement('input');
      input.type = 'file';
      input.accept = 'image/jpeg,image/png,image/webp,image/gif';
      input.onchange = () => uploadImageFile(input.files && input.files[0]);
      input.click();
    }

    // Quill 1.x não tem tabela nativa: registramos um "block embed" que
    // guarda o HTML da tabela inteiro, pra ela sobreviver no body_html ao
    // salvar e ser reconstruída ao reabrir o post pra editar.
    const BlockEmbed = Quill.import('blots/block/embed');
    class TableBlot extends BlockEmbed {
      static create(value) {
        const node = super.create();
        node.innerHTML = value;
        return node;
      }
      static value(node) {
        return node.innerHTML;
      }
    }
    TableBlot.blotName = 'ttTable';
    TableBlot.tagName = 'div';
    TableBlot.className = 'post-table-wrap';
    Quill.register(TableBlot);

    const quill = new Quill('#quill-editor', {
      theme: 'snow',
      modules: {
        toolbar: {
          container: [
            [{ header: [2, 3, false] }],
            ['bold', 'italic', 'underline', 'link'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['blockquote'],
            ['image'],
            ['clean'],
          ],
          handlers: { image: uploadEditorImage },
        },
      },
    });

    // Colar (Ctrl/Cmd+V) ou soltar uma imagem no editor: faz upload em vez
    // de o Quill embutir em base64. Usa a fase de captura (true) pra rodar
    // ANTES do handler interno do Quill e impedir o base64.
    quill.root.addEventListener('paste', (e) => {
      const items = (e.clipboardData && e.clipboardData.items) || [];
      const imageItem = [...items].find((it) => it.kind === 'file' && it.type && it.type.indexOf('image/') === 0);
      if (!imageItem) return; // texto/HTML: deixa o Quill tratar normalmente
      e.preventDefault();
      e.stopPropagation();
      uploadImageFile(imageItem.getAsFile());
    }, true);

    quill.root.addEventListener('drop', (e) => {
      const files = (e.dataTransfer && e.dataTransfer.files) || [];
      const images = [...files].filter((f) => f.type && f.type.indexOf('image/') === 0);
      if (!images.length) return;
      e.preventDefault();
      e.stopPropagation();
      images.forEach(uploadImageFile);
    }, true);

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

    // ---- Inserir tabela (botão + mini-formulário) ----
    const tableModal = document.getElementById('table-modal');
    const tblGrid = document.getElementById('tbl-grid');
    const tblCols = document.getElementById('tbl-cols');
    const tblRows = document.getElementById('tbl-rows');

    function escHtml(s) {
      return String(s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Monta os campos de input (linha 0 = cabeçalho). Preserva o que já foi
    // digitado ao regerar, e aceita cabeçalhos pré-preenchidos (preset).
    function buildTableGrid(headers) {
      const cols = Math.max(2, Math.min(6, parseInt(tblCols.value, 10) || 3));
      const rows = Math.max(1, Math.min(20, parseInt(tblRows.value, 10) || 4));
      tblCols.value = cols;
      tblRows.value = rows;

      const prev = {};
      tblGrid.querySelectorAll('.tbl-cell').forEach((el) => {
        prev[el.dataset.r + ':' + el.dataset.c] = el.value;
      });

      tblGrid.style.gridTemplateColumns = 'repeat(' + cols + ', 1fr)';
      tblGrid.innerHTML = '';
      for (let r = 0; r <= rows; r++) {
        for (let c = 0; c < cols; c++) {
          const inp = document.createElement('input');
          inp.type = 'text';
          inp.className = 'tbl-cell' + (r === 0 ? ' tbl-head' : '');
          inp.placeholder = r === 0 ? 'Cabeçalho ' + (c + 1) : '—';
          inp.dataset.r = String(r);
          inp.dataset.c = String(c);
          if (headers && r === 0 && headers[c] !== undefined) {
            inp.value = headers[c];
          } else if (prev[r + ':' + c] !== undefined) {
            inp.value = prev[r + ':' + c];
          }
          tblGrid.appendChild(inp);
        }
      }
    }

    const openTableModal = () => { buildTableGrid(); tableModal.hidden = false; };
    const closeTableModal = () => { tableModal.hidden = true; };

    document.getElementById('insert-table').addEventListener('click', openTableModal);
    document.getElementById('tbl-cancel').addEventListener('click', closeTableModal);
    document.getElementById('tbl-build').addEventListener('click', () => buildTableGrid());
    document.getElementById('tbl-preset').addEventListener('click', () => {
      tblCols.value = 3;
      tblRows.value = 4;
      buildTableGrid(['Critério', 'Eventos Corporativos', 'Formaturas e Casamentos']);
    });
    tableModal.addEventListener('click', (e) => { if (e.target === tableModal) closeTableModal(); });

    document.getElementById('tbl-insert').addEventListener('click', () => {
      const cols = Math.max(2, Math.min(6, parseInt(tblCols.value, 10) || 3));
      const rows = Math.max(1, Math.min(20, parseInt(tblRows.value, 10) || 4));
      const cell = (r, c) => tblGrid.querySelector('.tbl-cell[data-r="' + r + '"][data-c="' + c + '"]');

      let html = '<table class="post-table"><thead><tr>';
      for (let c = 0; c < cols; c++) html += '<th>' + escHtml(cell(0, c) ? cell(0, c).value : '') + '</th>';
      html += '</tr></thead><tbody>';
      for (let r = 1; r <= rows; r++) {
        html += '<tr>';
        for (let c = 0; c < cols; c++) html += '<td>' + escHtml(cell(r, c) ? cell(r, c).value : '') + '</td>';
        html += '</tr>';
      }
      html += '</tbody></table>';

      const range = quill.getSelection(true) || { index: quill.getLength() };
      quill.insertEmbed(range.index, 'ttTable', html, 'user');
      quill.setSelection(range.index + 1);
      closeTableModal();
    });
  </script>
</body>
</html>
