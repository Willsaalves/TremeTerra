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
 * 4. /sitemap.xml: arquivo físico é sitemap.php (gera XML dinamicamente,
 *    incluindo os posts do blog), mas a URL pública precisa ser .xml.
 * 5. 404 personalizado: se nada acima resolveu e o caminho não é um arquivo
 *    estático de verdade, serve dist/404.php com status 404 em vez de
 *    devolver o 404 cru do servidor embutido do PHP.
 *
 * Pra tudo mais (arquivo estático que realmente existe), `return false`
 * devolve o controle pro comportamento padrão do servidor embutido.
 *
 * 0. Cache-Control: o servidor embutido do PHP não define nenhum header
 *    de cache por padrão, então todo asset (JS/CSS/imagem/vídeo/fonte)
 *    seria sempre rebaixado do zero a cada visita. IMPORTANTE: quando o
 *    router devolve `false`, o servidor embutido do PHP serve o arquivo
 *    do zero (ignora qualquer header() setado antes do `return false`) —
 *    então pra tipos de arquivo "cacheáveis" a gente serve o arquivo
 *    manualmente aqui (readfile + Content-Type + Cache-Control) em vez de
 *    devolver `false`. Assets do Vite em /assets/ (nome com hash,
 *    imutável por build) ganham cache longo; demais arquivos estáticos
 *    (public/, sem hash) ganham cache curto; HTML/PHP (conteúdo dinâmico)
 *    continuam sem cache.
 */

$root = __DIR__;
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = $uri !== false && $uri !== null ? urldecode($uri) : '/';
$path = $root . $uri;
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

// Default: conteúdo dinâmico (HTML/PHP) sem cache. As rotas de vídeo e de
// arquivo estático abaixo sobrescrevem com seus próprios Cache-Control.
header('Cache-Control: no-cache');

// --- 1. Range requests pra vídeo ---
$videoMimes = ['mp4' => 'video/mp4', 'webm' => 'video/webm'];

if (isset($videoMimes[$ext]) && is_file($path)) {
    serveVideoWithRangeSupport($path, $videoMimes[$ext]);
    return true;
}

// --- 1b. Demais arquivos estáticos cacheáveis (servidos manualmente, ver
// nota "0. Cache-Control" acima sobre por que não dá pra usar `return false`
// aqui e ainda ter cache) ---
$staticMimes = [
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
    'webp' => 'image/webp', 'gif' => 'image/gif', 'svg' => 'image/svg+xml',
    'ico' => 'image/x-icon',
    'woff' => 'font/woff', 'woff2' => 'font/woff2',
    'ttf' => 'font/ttf', 'otf' => 'font/otf',
    'css' => 'text/css', 'js' => 'application/javascript',
    'json' => 'application/json', 'webmanifest' => 'application/manifest+json',
];
if (isset($staticMimes[$ext]) && is_file($path)) {
    $cache = str_starts_with($uri, '/assets/')
        ? 'public, max-age=31536000, immutable' // nome com hash do Vite, imutável por build
        : 'public, max-age=2592000'; // public/, sem hash — 30 dias (curto o bastante pra corrigir um upload errado sem esperar muito, longo o bastante pra passar no audit de cache do Lighthouse)
    header('Content-Type: ' . $staticMimes[$ext]);
    header('Cache-Control: ' . $cache);

    // Compressão gzip pros tipos textuais (CSS/JS/SVG/JSON/manifest), quando o
    // cliente aceita — o servidor embutido do PHP não comprime nada sozinho.
    // Imagens/fontes já são formatos comprimidos, não entram aqui.
    $gzippable = in_array($ext, ['css', 'js', 'svg', 'json', 'webmanifest'], true);
    $acceptsGzip = str_contains($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip');
    if ($gzippable && $acceptsGzip) {
        $data = gzencode((string) file_get_contents($path), 6);
        header('Content-Encoding: gzip');
        header('Vary: Accept-Encoding');
        header('Content-Length: ' . (string) strlen($data));
        echo $data;
        return true;
    }

    header('Content-Length: ' . (string) filesize($path));
    readfile($path);
    return true;
}

// --- 1c. Imagens enviadas pelo admin do blog ---
// Ficam num diretório fora do dist/ (Persistent Disk em produção), então o
// servidor de arquivos estático embutido não as encontra sozinho. O regex
// restrito ([a-z0-9] + extensão) impede path traversal. ---
if (preg_match('#^/uploads/([a-zA-Z0-9]+\.(?:jpe?g|png|webp|gif))$#', $uri, $upMatch)) {
    require_once __DIR__ . '/lib/uploads.php';
    $uploadPath = blogUploadDir() . '/' . $upMatch[1];
    $uploadMime = blogUploadContentType($uploadPath);
    if ($uploadMime !== null && is_file($uploadPath)) {
        header('Content-Type: ' . $uploadMime);
        header('Cache-Control: public, max-age=2592000');
        header('Content-Length: ' . (string) filesize($uploadPath));
        readfile($uploadPath);
        return true;
    }
}

// --- 1d. Redirects 301 de URLs legadas (páginas .php antigas por cidade/
// estado e taxonomias de blog do site anterior) pras páginas canônicas
// atuais. Consolida dezenas de URLs de SEO antigas num único destino, sem
// deixar 404. Casado por prefixo/família — as variantes por cidade/estado e
// com/sem .php caem todas no mesmo destino. Vem depois de assets estáticos
// (que já retornaram acima) e antes do roteamento normal. ---
$legacyRedirects = [
    // aluguel de som (todas as cidades/estados + "melhor aluguel") -> aluguel-som-profissional
    '#^/(?:melhor-)?aluguel-de-som-para-eventos#' => '/aluguel-som-profissional/',
    // banda para eventos corporativos -> shows
    '#^/banda-para-eventos-corporativos#' => '/shows/',
    // dj para eventos por cidade/estado -> dj-para-eventos (não pega /dj-para-eventos/ nem /dj-para-casamentos/)
    '#^/dj-para-eventos-(?:em|no)-#' => '/dj-para-eventos/',
    // empresa de eventos (+ páginas institucionais antigas) -> empresa-audiovisual
    '#^/empresa-de-eventos#' => '/empresa-audiovisual/',
    '#^/sobre-nos(?:\.php)?$#' => '/empresa-audiovisual/',
    '#^/diferencial(?:\.php)?$#' => '/empresa-audiovisual/',
    '#^/informacoes(?:\.php)?$#' => '/empresa-audiovisual/',
    // iluminação para eventos -> iluminacao-para-festas
    '#^/iluminacao-para-eventos#' => '/iluminacao-para-festas/',
    // painel de led para eventos -> painel-de-led
    '#^/painel-de-led-para-eventos#' => '/painel-de-led/',
    // produtora de eventos (todas as variantes) -> produtora-de-eventos-corporativos
    '#^/produtora-de-eventos#' => '/produtora-de-eventos-corporativos/',
    // taxonomias e index antigos do blog (WordPress) -> /blog/
    '#^/blog/(?:author|category|tag|page)/#' => '/blog/',
    '#^/blog/home/?$#' => '/blog/',
];

foreach ($legacyRedirects as $pattern => $target) {
    if (preg_match($pattern, $uri)) {
        // Evita redirecionar a própria página canônica pra ela mesma
        // (ex.: /produtora-de-eventos-corporativos/ casa o prefixo, mas já é
        // o destino) — deixa o roteamento normal servir.
        if (rtrim($uri, '/') === rtrim($target, '/')) {
            break;
        }
        $query = $_SERVER['QUERY_STRING'] ?? '';
        http_response_code(301);
        header('Location: ' . $target . ($query !== '' ? '?' . $query : ''));
        return true;
    }
}

// --- 2. Home ("/") -> index.php ---
if ($uri === '/') {
    $indexPath = $root . '/index.php';
    if (is_file($indexPath)) {
        chdir($root);
        require $indexPath;
        return true;
    }
}

// --- 2a. URL canônica com barra final: se a URL limpa (sem extensão, sem
// barra no final) corresponde a uma página real, redireciona 301 pra versão
// com barra — a mesma que os links internos (nav/footer) e o <link
// rel="canonical"> já usam. Sem isso, as duas variantes (com e sem barra)
// serviriam o mesmo conteúdo com 200, o que é duplicação de conteúdo pro
// Google. Não afeta /blog/{slug} (rota dinâmica, sem arquivo .php próprio,
// tratada na regra 3 mais abaixo, que já aceita a barra opcional). ---
if ($uri !== '/' && !str_ends_with($uri, '/') && !is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === '') {
    $phpPath = $path . '.php';
    if (is_file($phpPath)) {
        $query = $_SERVER['QUERY_STRING'] ?? '';
        $location = $uri . '/' . ($query !== '' ? '?' . $query : '');
        http_response_code(301);
        header('Location: ' . $location);
        return true;
    }
}

// --- 2b. URLs sem extensão -> arquivo .php correspondente ---
if ($uri !== '/' && !is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === '') {
    $phpPath = rtrim($path, '/') . '.php';
    if (is_file($phpPath)) {
        chdir(dirname($phpPath));
        require $phpPath;
        return true;
    }
}

// --- 2c. Sitemap do blog: /blog/page-sitemap.xml -> blog-sitemap.php ---
// Precisa vir ANTES da regra de /blog/{slug} abaixo, senão o slug capturaria
// "page-sitemap.xml" e cairia no blog-post.php (404).
if ($uri === '/blog/page-sitemap.xml') {
    $blogSitemap = $root . '/blog-sitemap.php';
    if (is_file($blogSitemap)) {
        chdir($root);
        require $blogSitemap;
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

// --- 4. /sitemap.xml -> sitemap.php (índice) e /pages-sitemap.xml ---
if ($uri === '/sitemap.xml') {
    $sitemapPath = $root . '/sitemap.php';
    if (is_file($sitemapPath)) {
        chdir($root);
        require $sitemapPath;
        return true;
    }
}
if ($uri === '/pages-sitemap.xml') {
    $pagesSitemap = $root . '/pages-sitemap.php';
    if (is_file($pagesSitemap)) {
        chdir($root);
        require $pagesSitemap;
        return true;
    }
}

// --- 5. 404 personalizado ---
if (!is_file($path)) {
    $notFoundPath = $root . '/404.php';
    if (is_file($notFoundPath)) {
        http_response_code(404);
        chdir($root);
        require $notFoundPath;
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
        // Resposta 200 do arquivo inteiro: pode ser cacheada por navegador/
        // proxy sem ambiguidade (é o arquivo completo, sempre).
        header('Content-Type: ' . $mime);
        header('Accept-Ranges: bytes');
        header('Content-Length: ' . $size);
        header('Cache-Control: public, max-age=2592000');
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

    // Resposta 206 (trecho de bytes): NUNCA cachear publicamente. Um cache
    // (navegador agressivo, proxy/CDN na frente do Render) que guardasse
    // isso sob a mesma chave da URL devolveria o trecho errado pra um seek
    // diferente do vídeo, corrompendo o stream e travando o quadro em preto.
    http_response_code(206);
    header('Content-Type: ' . $mime);
    header('Accept-Ranges: bytes');
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
    header('Content-Length: ' . (string) $length);
    header('Cache-Control: no-store');

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
