<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/require-admin.php';

$db = getDb();
$leads = $db->query(
    'SELECT id, nome, telefone, email, tipo_evento, mensagem, pagina, enviado_ac, created_at
     FROM leads ORDER BY created_at DESC, id DESC'
)->fetchAll();

// Exportação CSV — ?export=csv baixa todos os leads numa planilha.
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="leads-tremeterra.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, "\xEF\xBB\xBF"); // BOM pro Excel abrir acentos certo
    fputcsv($out, ['Data', 'Nome', 'Telefone', 'E-mail', 'Tipo de evento', 'Mensagem', 'Página', 'Enviado à AC']);
    foreach ($leads as $l) {
        fputcsv($out, [
            blogDateBR((string) $l['created_at']), $l['nome'], $l['telefone'], $l['email'],
            $l['tipo_evento'], $l['mensagem'], $l['pagina'],
            $l['enviado_ac'] ? 'sim' : 'não',
        ]);
    }
    fclose($out);
    exit;
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Leads do formulário | Admin — <?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="robots" content="noindex, nofollow" />
  <link rel="stylesheet" href="/blog.css" />
</head>
<body class="admin-body">
  <header class="admin-header">
    <div class="container admin-header-inner">
      <strong>Admin do blog</strong>
      <nav>
        <a href="/admin/">Posts</a>
        <a href="/blog/">Ver blog</a>
        <a href="/admin/logout.php">Sair</a>
      </nav>
    </div>
  </header>

  <main class="container admin-main">
    <div class="admin-main-head">
      <h1>Leads do formulário <span style="font-weight:400;opacity:.6;font-size:.7em;">(<?= count($leads) ?>)</span></h1>
      <?php if (!empty($leads)): ?>
        <a href="/admin/leads.php?export=csv" class="btn-admin">Baixar CSV</a>
      <?php endif; ?>
    </div>

    <?php if (empty($leads)): ?>
      <p class="admin-empty">Nenhuma solicitação de orçamento ainda. Assim que alguém enviar o formulário do site, aparece aqui.</p>
    <?php else: ?>
      <div style="overflow-x:auto;">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Data</th>
              <th>Nome</th>
              <th>Contato</th>
              <th>Tipo de evento</th>
              <th>Mensagem</th>
              <th>Página</th>
              <th>AC</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($leads as $l): ?>
              <tr>
                <td style="white-space:nowrap;"><?= htmlspecialchars(blogDateBR((string) $l['created_at']), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) $l['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <a href="tel:<?= htmlspecialchars(preg_replace('/\D+/', '', (string) $l['telefone']) ?? '', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $l['telefone'], ENT_QUOTES, 'UTF-8') ?></a><br />
                  <a href="mailto:<?= htmlspecialchars((string) $l['email'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $l['email'], ENT_QUOTES, 'UTF-8') ?></a>
                </td>
                <td><?= htmlspecialchars((string) $l['tipo_evento'], ENT_QUOTES, 'UTF-8') ?></td>
                <td style="max-width:320px;"><?= nl2br(htmlspecialchars((string) $l['mensagem'], ENT_QUOTES, 'UTF-8')) ?></td>
                <td><?= htmlspecialchars((string) $l['pagina'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= $l['enviado_ac'] ? '✓' : '—' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>
