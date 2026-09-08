<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['is_admin'])) {
    header('Location: /admin/index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $hash = getenv('ADMIN_PASSWORD_HASH') ?: '';

    if ($username === 'admin' && $hash !== '' && password_verify($password, $hash)) {
        session_regenerate_id(true);
        $_SESSION['is_admin'] = true;
        header('Location: /admin/index.php');
        exit;
    }

    $error = 'Usuário ou senha incorretos.';
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login | Admin do blog — <?= htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="robots" content="noindex, nofollow" />
  <link rel="stylesheet" href="/blog.css?v=<?= @filemtime(__DIR__ . "/../blog.css") ?: 1 ?>" />
</head>
<body class="admin-body">
  <main class="admin-login">
    <form method="post" class="admin-login-card">
      <h1>Admin do blog</h1>
      <?php if ($error): ?>
        <p class="admin-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
      <label>Usuário
        <input type="text" name="username" autocomplete="username" required />
      </label>
      <label>Senha
        <input type="password" name="password" autocomplete="current-password" required />
      </label>
      <button type="submit" class="btn-admin">Entrar</button>
    </form>
  </main>
</body>
</html>
