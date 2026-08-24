<?php
declare(strict_types=1);

// Endpoint AJAX pro upload de imagem do corpo do post (botão de imagem do
// editor Quill). Exige admin logado + token CSRF, grava via lib/uploads.php
// e devolve JSON { url } (ou { error }).
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/require-admin.php';
require_once __DIR__ . '/../lib/uploads.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido.']);
    exit;
}

assertCsrf();

try {
    $url = handleBlogImageUpload($_FILES['image'] ?? []);
    if ($url === null) {
        http_response_code(422);
        echo json_encode(['error' => 'Nenhum arquivo enviado.']);
        exit;
    }
    echo json_encode(['url' => $url]);
} catch (RuntimeException $e) {
    http_response_code(422);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
