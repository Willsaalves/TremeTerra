<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['is_admin'])) {
    header('Location: /admin/login.php');
    exit;
}

// Token CSRF simples pra formulários de POST do admin (criar, salvar, excluir).
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrfToken(): string
{
    return $_SESSION['csrf_token'] ?? '';
}

function assertCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Sessão expirada ou inválida. Volte e tente de novo.');
    }
}
