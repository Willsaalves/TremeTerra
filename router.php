<?php
declare(strict_types=1);

/**
 * Router pro servidor embutido do PHP (`php -S ... -t dist dist/router.php`),
 * usado tanto localmente (`npm run serve:php`) quanto no deploy (Dockerfile).
 * Resolve dois problemas que o servidor embutido não cobre sozinho:
 *
 * 1. Vídeo com HTTP Range: o servidor embutido do PHP ignora o header
 *    `Range` e sempre devolve o arquivo inteiro com 200 OK — isso quebra o
 *    seek de vídeo (necessário pro scroll-scrub de `.film-video`), porque o
 *    navegador pede um trecho específico e recebe o arquivo todo de volta.
 *    Servimos vídeo (.mp4/.webm) manualmente aqui com 206 Partial Content
 *    de verdade quando o header Range vem na requisição.
 * 2. URLs limpas sem extensão: as páginas internas (ex:
 *    /produtora-de-eventos-corporativos) são arquivos .php de verdade no
 *    dist/ (sem subpastas), mas o servidor embutido só serve pela URL exata
 *    do arquivo. Aqui a gente reescreve pra servir o .php correspondente
 *    quando a URL pedida não tem extensão.
 * 3. Posts do blog: /blog/{slug} não é um arquivo .php de verdade (o slug é
 *    dinâmico, vem do banco) — roteia pra blog-post.php?slug={slug}.
 *
 * Pra tudo mais, `return false` devolve o controle pro comportamento padrão
 * do servidor embutido (arquivo estático normal ou index.php da raiz).
 */

$root = __DIR__;
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = $uri !== false && $uri !== null ? urldecode($uri) : '/';
$path = $root . $uri;

// --- 1. Range requests pra vídeo ---
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$videoMimes = ['mp4' => 'video/mp4', 'webm' => 'video/webm'];

if (isset($videoMimes[$ext]) && is_file($path)) {
    serveVideoWithRangeSupport($path, $videoMimes[$ext]);
    return true;
}

// --- 2. URLs sem extensão -> arquivo .php correspondente ---
if ($uri !== '/' && !is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === '') {
    $phpPath = rtrim($path, '/') . '.php';
    if (is_file($phpPath)) {
        chdir(dirname($phpPath));
        require $phpPath;
        return true;
    }
}

// --- 3. /blog/{slug} -> blog-post.php?slug={slug} ---
// Só entra aqui se não for um arquivo estático de verdade (ex: uma imagem
// de capa em /blog/algo.jpg) — senão essa regra capturaria as imagens do
// blog antes delas chegarem no servidor de arquivos estáticos.
if (!is_file($path) && preg_match('#^/blog/([^/]+)/?$#', $uri, $matches)) {
    $slugPath = $root . '/blog-post.php';
    if (is_file($slugPath)) {
        $_GET['slug'] = $matches[1];
        chdir($root);
        require $slugPath;
        return true;
    }
}

return false;

function serveVideoWithRangeSupport(string $path, string $mime): void
{
    $size = filesize($path);
    if ($size === false) {
        http_response_code(500);
        return;
    }

    $range = $_SERVER['HTTP_RANGE'] ?? null;

    if ($range === null) {
        header('Content-Type: ' . $mime);
        header('Accept-Ranges: bytes');
        header('Content-Length: ' . $size);
        readfile($path);
        return;
    }

    if (!preg_match('/bytes=(\d*)-(\d*)/', $range, $matches)) {
        http_response_code(416);
        header('Content-Range: bytes */' . $size);
        return;
    }

    $start = $matches[1] === '' ? 0 : (int) $matches[1];
    $end = $matches[2] === '' ? $size - 1 : (int) $matches[2];
    $end = min($end, $size - 1);

    if ($start > $end || $start >= $size) {
        http_response_code(416);
        header('Content-Range: bytes */' . $size);
        return;
    }

    $length = $end - $start + 1;

    http_response_code(206);
    header('Content-Type: ' . $mime);
    header('Accept-Ranges: bytes');
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
    header('Content-Length: ' . (string) $length);

    $fp = fopen($path, 'rb');
    if ($fp === false) {
        http_response_code(500);
        return;
    }
    fseek($fp, $start);
    $remaining = $length;
    while ($remaining > 0 && !feof($fp)) {
        $chunk = min(8192, $remaining);
        echo fread($fp, $chunk);
        $remaining -= $chunk;
    }
    fclose($fp);
}
