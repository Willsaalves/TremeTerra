<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

// Backup dos posts do blog. Duas camadas:
//  1) Snapshot automático em JSON a cada salvamento (writePostsSnapshot),
//     gravado ao lado do banco — no disco persistente, sobrevive a deploys.
//  2) Exportação/restauração manual pelo admin (/admin/backup.php): baixar o
//     .sqlite inteiro, exportar posts em JSON e restaurar a partir de um JSON.
//
// IMPORTANTE: só é backup DE VERDADE se ficar em armazenamento durável. Sem o
// disco persistente do Render, tanto o banco quanto os snapshots são apagados
// a cada deploy — por isso o admin também permite BAIXAR o backup pro seu
// computador/drive, que é o único lugar 100% seguro nesse cenário.

/** Diretório onde os snapshots automáticos são gravados (ao lado do banco). */
function blogBackupDir(): string
{
    $custom = getenv('BLOG_BACKUP_DIR');
    if ($custom !== false && $custom !== '') {
        return rtrim($custom, '/');
    }
    $dbPath = getenv('SQLITE_DB_PATH') ?: (dirname(__DIR__) . '/data/blog.sqlite');
    return dirname($dbPath) . '/backups';
}

/** Caminho do arquivo físico do banco SQLite em uso. */
function blogDbPath(): string
{
    return getenv('SQLITE_DB_PATH') ?: (dirname(__DIR__) . '/data/blog.sqlite');
}

/**
 * Todos os posts como array associativo (para exportar em JSON).
 * @return array<int, array<string, mixed>>
 */
function exportPostsArray(PDO $db): array
{
    return $db->query('SELECT * FROM posts ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Grava um snapshot dos posts em JSON ao lado do banco. Mantém o "posts-latest
 * .json" sempre atual + um arquivo com timestamp (mantendo os últimos $keep).
 * Nunca lança — falha de backup não pode derrubar o salvamento do post.
 */
function writePostsSnapshot(PDO $db, int $keep = 10): void
{
    try {
        $dir = blogBackupDir();
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return;
        }
        $posts = exportPostsArray($db);
        $payload = json_encode(
            ['exported_at' => gmdate('c'), 'count' => count($posts), 'posts' => $posts],
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );
        if ($payload === false) {
            return;
        }
        file_put_contents($dir . '/posts-latest.json', $payload);
        file_put_contents($dir . '/posts-' . gmdate('Ymd-His') . '.json', $payload);

        // Poda: mantém só os $keep snapshots com timestamp mais recentes.
        $timestamped = glob($dir . '/posts-*-*.json') ?: [];
        if (count($timestamped) > $keep) {
            sort($timestamped);
            foreach (array_slice($timestamped, 0, count($timestamped) - $keep) as $old) {
                @unlink($old);
            }
        }
    } catch (Throwable $e) {
        error_log('[backup] Falha ao gravar snapshot de posts: ' . $e->getMessage());
    }
}

/**
 * Restaura posts a partir de um array (vindo de um JSON exportado). Faz UPSERT
 * por slug: atualiza se o slug já existe, insere se não. Não apaga nada que já
 * esteja no banco. Retorna [inseridos, atualizados].
 * @param array<int, array<string, mixed>> $posts
 * @return array{0:int,1:int}
 */
function restorePostsFromArray(PDO $db, array $posts): array
{
    $cols = ['title','slug','category','seo_title','seo_description','direct_answer',
             'cover_image_url','cover_image_alt','body_html','faq_json','status',
             'published_at','created_at','updated_at'];

    $find = $db->prepare('SELECT id FROM posts WHERE slug = ?');
    $insert = $db->prepare(
        'INSERT INTO posts (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')'
    );
    $updateCols = array_filter($cols, static fn ($c) => $c !== 'slug');
    $update = $db->prepare(
        'UPDATE posts SET ' . implode(', ', array_map(static fn ($c) => "$c = ?", $updateCols)) . ' WHERE slug = ?'
    );

    $inserted = 0; $updated = 0;
    foreach ($posts as $p) {
        $slug = trim((string) ($p['slug'] ?? ''));
        if ($slug === '') {
            continue;
        }
        $val = static fn (string $c) => array_key_exists($c, $p) ? $p[$c] : null;
        $find->execute([$slug]);
        if ($find->fetchColumn() !== false) {
            $update->execute([...array_map($val, $updateCols), $slug]);
            $updated++;
        } else {
            $insert->execute(array_map($val, $cols));
            $inserted++;
        }
    }
    return [$inserted, $updated];
}
