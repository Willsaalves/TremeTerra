<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/require-admin.php';
require_once __DIR__ . '/../lib/backup.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/index.php');
    exit;
}

assertCsrf();

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id > 0) {
    $db = getDb();
    // Snapshot ANTES de apagar — assim o post excluído fica preservado num
    // arquivo com timestamp, dando como desfazer uma exclusão acidental.
    writePostsSnapshot($db);
    $stmt = $db->prepare('DELETE FROM posts WHERE id = ?');
    $stmt->execute([$id]);
}

header('Location: /admin/index.php');
exit;
