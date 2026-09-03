<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/require-admin.php';
require_once __DIR__ . '/../lib/backup.php';

$db = getDb();
$message = null;
$error = null;

// --- Ações ---------------------------------------------------------------
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// Baixar o banco SQLite inteiro (backup completo: posts + leads).
if ($action === 'download_db') {
    $path = blogDbPath();
    if (!is_file($path)) {
        http_response_code(404);
        exit('Banco não encontrado.');
    }
    header('Content-Type: application/x-sqlite3');
    header('Content-Disposition: attachment; filename="tremeterra-backup-' . gmdate('Ymd-His') . '.sqlite"');
    header('Content-Length: ' . (string) filesize($path));
    readfile($path);
    exit;
}

// Exportar todos os posts em JSON (portátil, restaurável abaixo).
if ($action === 'export_posts') {
    $posts = exportPostsArray($db);
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="tremeterra-posts-' . gmdate('Ymd-His') . '.json"');
    echo json_encode(
        ['exported_at' => gmdate('c'), 'count' => count($posts), 'posts' => $posts],
        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
    );
    exit;
}

// Restaurar posts a partir de um JSON enviado.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'restore') {
    assertCsrf();
    $file = $_FILES['backup_file'] ?? [];
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $error = 'Selecione um arquivo .json de backup válido.';
    } else {
        $raw = file_get_contents($file['tmp_name']);
        $data = json_decode((string) $raw, true);
        $posts = is_array($data) ? ($data['posts'] ?? $data) : null;
        if (!is_array($posts) || $posts === []) {
            $error = 'Arquivo inválido: não encontrei posts no JSON.';
        } else {
            [$ins, $upd] = restorePostsFromArray($db, $posts);
            $message = "Restauração concluída: {$ins} post(s) inserido(s), {$upd} atualizado(s).";
        }
    }
}

// Snapshots automáticos existentes (para referência).
$snapshots = [];
$latest = blogBackupDir() . '/posts-latest.json';
if (is_file($latest)) {
    $snapshots['latest'] = date('d/m/Y H:i', filemtime($latest));
}
$countPosts = (int) $db->query('SELECT COUNT(*) FROM posts')->fetchColumn();
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Backup | Admin — <?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="robots" content="noindex, nofollow" />
  <link rel="stylesheet" href="/blog.css" />
</head>
<body class="admin-body">
  <header class="admin-header">
    <div class="container admin-header-inner">
      <strong>Admin do blog</strong>
      <nav>
        <a href="/admin/">Posts</a>
        <a href="/admin/leads.php">Leads</a>
        <a href="/blog/">Ver blog</a>
        <a href="/admin/logout.php">Sair</a>
      </nav>
    </div>
  </header>

  <main class="container admin-main">
    <div class="admin-main-head">
      <h1>Backup &amp; Restauração</h1>
    </div>

    <?php if ($message !== null): ?>
      <p class="admin-empty" style="border-left:3px solid #2e7d32;"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if ($error !== null): ?>
      <p class="admin-empty" style="border-left:3px solid #c62828;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <p style="max-width:70ch;line-height:1.6;">
      Há <strong><?= $countPosts ?></strong> post(s) no banco agora.
      <?php if (isset($snapshots['latest'])): ?>
        Último snapshot automático: <strong><?= htmlspecialchars($snapshots['latest'], ENT_QUOTES, 'UTF-8') ?></strong>.
      <?php endif; ?>
      Recomendamos <strong>baixar um backup com frequência</strong> e guardar no seu
      Drive/computador — é a cópia mais segura.
    </p>

    <section style="margin-top:2rem;display:grid;gap:1.5rem;max-width:70ch;">
      <div>
        <h2 style="font-size:1.1rem;margin:0 0 .5rem;">Baixar backup</h2>
        <p style="margin:0 0 .75rem;opacity:.8;">Guarde estes arquivos num lugar seguro (Drive, e-mail, computador).</p>
        <a class="btn-admin" href="/admin/backup.php?action=export_posts">Exportar posts (.json)</a>
        <a class="btn-admin" href="/admin/backup.php?action=download_db" style="margin-left:.5rem;">Baixar banco completo (.sqlite)</a>
      </div>

      <div>
        <h2 style="font-size:1.1rem;margin:0 0 .5rem;">Restaurar posts</h2>
        <p style="margin:0 0 .75rem;opacity:.8;">
          Envie um arquivo <code>.json</code> exportado aqui. Ele repõe os posts
          (atualiza os que já existem pelo mesmo slug e insere os que faltam). Não apaga nada.
        </p>
        <form method="post" action="/admin/backup.php?action=restore" enctype="multipart/form-data">
          <input type="hidden" name="action" value="restore" />
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>" />
          <input type="file" name="backup_file" accept="application/json,.json" required />
          <button type="submit" class="btn-admin" style="margin-left:.5rem;">Restaurar</button>
        </form>
      </div>
    </section>
  </main>
</body>
</html>
