<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/require-admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/index.php');
    exit;
}

assertCsrf();

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id > 0) {
    $stmt = getDb()->prepare('DELETE FROM posts WHERE id = ?');
    $stmt->execute([$id]);
}

header('Location: /admin/index.php');
exit;
