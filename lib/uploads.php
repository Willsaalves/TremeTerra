<?php
declare(strict_types=1);

// Upload de imagens do blog. As imagens são gravadas num diretório fora do
// dist/ (conteúdo estático versionado) — em produção, no mesmo Persistent
// Disk do SQLite (ex.: /var/data/uploads), que sobrevive a redeploys. O
// router.php serve /uploads/<arquivo> lendo desse diretório.

// Formatos aceitos (MIME real detectado via finfo => extensão canônica).
const BLOG_UPLOAD_MIME = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
];
const BLOG_UPLOAD_MAX_BYTES = 5 * 1024 * 1024; // 5 MB

// Diretório de destino. Ordem: BLOG_UPLOAD_DIR explícito -> pasta uploads/ ao
// lado do SQLITE_DB_PATH (mesmo disco persistente) -> data/uploads local (dev).
function blogUploadDir(): string
{
    $dir = getenv('BLOG_UPLOAD_DIR') ?: '';
    if ($dir === '') {
        $dbPath = getenv('SQLITE_DB_PATH') ?: '';
        $dir = $dbPath !== '' ? dirname($dbPath) . '/uploads' : __DIR__ . '/../data/uploads';
    }
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return rtrim($dir, '/');
}

// Content-Type pra servir uma imagem já salva (usado pelo router).
function blogUploadContentType(string $path): ?string
{
    return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
        'jpg', 'jpeg' => 'image/jpeg',
        'png'         => 'image/png',
        'webp'        => 'image/webp',
        'gif'         => 'image/gif',
        default       => null,
    };
}

/**
 * Valida e grava um item de $_FILES. Devolve a URL pública (/uploads/xxx) em
 * caso de sucesso, null se nenhum arquivo foi enviado, ou lança
 * RuntimeException com mensagem amigável quando algo está errado.
 *
 * @param array<string, mixed> $file item de $_FILES
 */
function handleBlogImageUpload(array $file): ?string
{
    // Campo vazio (nenhum arquivo selecionado) não é erro — só não faz nada.
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        // INI_SIZE/FORM_SIZE = arquivo maior que o limite do PHP/formulário.
        if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            throw new RuntimeException('Imagem muito grande. O limite é 5 MB.');
        }
        throw new RuntimeException('Falha no upload da imagem (código ' . (int) $file['error'] . ').');
    }
    if (($file['size'] ?? 0) > BLOG_UPLOAD_MAX_BYTES) {
        throw new RuntimeException('Imagem muito grande. O limite é 5 MB.');
    }
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Upload inválido.');
    }

    // Confia no MIME real do conteúdo (finfo), não na extensão nem no
    // Content-Type enviado pelo navegador (ambos falsificáveis).
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!is_string($mime) || !isset(BLOG_UPLOAD_MIME[$mime])) {
        throw new RuntimeException('Formato não suportado. Use JPG, PNG, WebP ou GIF.');
    }

    $ext  = BLOG_UPLOAD_MIME[$mime];
    // Nome aleatório (não confia no nome original — evita colisão e path
    // traversal). Só [a-f0-9] + extensão canônica, casando com o regex do router.
    $name = bin2hex(random_bytes(10)) . '.' . $ext;
    $dest = blogUploadDir() . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Não foi possível salvar a imagem no servidor.');
    }

    return '/uploads/' . $name;
}
