<?php
declare(strict_types=1);

// Conexão PDO SQLite pro blog. Em produção (Render), SQLITE_DB_PATH aponta
// pro arquivo dentro do Persistent Disk (ex: /var/data/blog.sqlite), que
// sobrevive a redeploys. Sem a env var (dev local), cai num arquivo dentro
// de data/ na raiz do projeto — criado automaticamente na primeira conexão.
function getDb(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $path = getenv('SQLITE_DB_PATH') ?: (__DIR__ . '/../data/blog.sqlite');
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $pdo = new PDO('sqlite:' . $path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    $pdo->exec('
        CREATE TABLE IF NOT EXISTS posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            category TEXT,
            seo_title TEXT,
            seo_description TEXT,
            direct_answer TEXT,
            cover_image_url TEXT,
            cover_image_alt TEXT,
            body_html TEXT,
            faq_json TEXT,
            status TEXT NOT NULL DEFAULT "draft",
            published_at TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ');

    return $pdo;
}

const POST_CATEGORIES = [
    'locacao'     => 'Locação de equipamentos',
    'corporativo' => 'Eventos corporativos',
    'social'      => 'Casamentos e formaturas',
    'producao'    => 'Produção e shows',
];

function slugify(string $text): string
{
    $text = trim($text);
    // Remove acentos.
    $translit = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    if ($translit !== false) {
        $text = $translit;
    }
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}
