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

    // Toda submissão do formulário de orçamento fica gravada aqui — assim o
    // lead nunca se perde, mesmo que a ActiveCampaign esteja fora do ar ou
    // não configurada. Visível no admin em /admin/leads.php.
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS leads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            telefone TEXT NOT NULL,
            email TEXT NOT NULL,
            tipo_evento TEXT,
            mensagem TEXT,
            pagina TEXT,
            enviado_ac INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ');

    return $pdo;
}

const POST_CATEGORIES = [
    'locacao'     => 'Locação de equipamentos',
    'corporativo' => 'Eventos corporativos',
    'social'      => 'Casamentos e formaturas',
    'producao'    => 'Produção e shows',
    'convidados'  => 'Convidados',
];

/**
 * Um post pode ter MAIS DE UMA categoria. No banco, a coluna `category`
 * guarda os slugs separados por vírgula (ex.: "convidados,corporativo");
 * um post com uma categoria só continua sendo um slug único, então os
 * posts antigos seguem funcionando sem migração.
 *
 * Devolve só os slugs válidos (que existem em POST_CATEGORIES), sem
 * repetição e na ordem em que aparecem.
 *
 * @return list<string>
 */
function postCategories(?string $raw): array
{
    if ($raw === null || trim($raw) === '') {
        return [];
    }
    $valid = [];
    foreach (explode(',', $raw) as $slug) {
        $slug = trim($slug);
        if ($slug !== '' && isset(POST_CATEGORIES[$slug]) && !in_array($slug, $valid, true)) {
            $valid[] = $slug;
        }
    }
    return $valid;
}

/**
 * Mapa slug => rótulo das categorias de um post, na ordem salva.
 *
 * @return array<string, string>
 */
function postCategoryLabels(?string $raw): array
{
    $labels = [];
    foreach (postCategories($raw) as $slug) {
        $labels[$slug] = POST_CATEGORIES[$slug];
    }
    return $labels;
}

/**
 * Normaliza a entrada do formulário (array de checkboxes OU string legada)
 * para o formato de armazenamento: slugs válidos separados por vírgula.
 */
function normalizePostCategories(mixed $input): string
{
    if (is_array($input)) {
        $raw = implode(',', array_map('strval', $input));
    } else {
        $raw = (string) $input;
    }
    return implode(',', postCategories($raw));
}

/**
 * Condição SQL para os posts que estão "no ar": não-rascunho E cujo horário
 * de publicação já chegou. `published_at` é gravado em UTC (gmdate); o
 * `datetime('now')` do SQLite também é UTC, e os dois têm o mesmo formato
 * "Y-m-d H:i:s", então a comparação textual funciona direto — sem parâmetro.
 *
 * É isto que faz o AGENDAMENTO funcionar SEM cron: um post agendado tem
 * `published_at` no futuro, então fica de fora até a hora chegar; assim que
 * `datetime('now')` passa do horário, ele entra sozinho na listagem.
 *
 * A string é constante (sem entrada do usuário), segura pra interpolar no SQL.
 */
function livePostsSql(): string
{
    return "status != 'draft' AND published_at IS NOT NULL AND published_at <= datetime('now')";
}

/**
 * Status "efetivo" de um post pra exibição no admin, considerando o relógio:
 * - 'draft'     → rascunho
 * - 'scheduled' com horário ainda no futuro → agendado
 * - caso contrário (publicado, ou agendado cujo horário já passou) → publicado
 */
function effectivePostStatus(array $post): string
{
    $status = (string) ($post['status'] ?? 'draft');
    if ($status === 'draft') {
        return 'draft';
    }
    $publishedAt = trim((string) ($post['published_at'] ?? ''));
    if ($publishedAt === '') {
        return 'draft';
    }
    if ($publishedAt > gmdate('Y-m-d H:i:s')) {
        return 'scheduled';
    }
    return 'published';
}

/**
 * Converte um datetime-local do admin (digitado em horário de São Paulo, ex.:
 * "2026-11-05T14:30") para o timestamp UTC "Y-m-d H:i:s" que o banco guarda.
 * Devolve null se a entrada estiver vazia ou inválida.
 */
function parseScheduledToUtc(string $localDateTime): ?string
{
    $localDateTime = trim($localDateTime);
    if ($localDateTime === '') {
        return null;
    }
    try {
        $dt = new DateTime($localDateTime, new DateTimeZone(SITE_TIMEZONE));
        $dt->setTimezone(new DateTimeZone('UTC'));
        return $dt->format('Y-m-d H:i:s');
    } catch (Throwable) {
        return null;
    }
}

/**
 * Timestamp UTC do banco → valor pronto pro input datetime-local (horário de
 * São Paulo, formato "Y-m-d\TH:i"). Vazio se não houver data.
 */
function utcToLocalInput(?string $utc): string
{
    $utc = trim((string) $utc);
    if ($utc === '') {
        return '';
    }
    try {
        $dt = new DateTime($utc, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone(SITE_TIMEZONE));
        return $dt->format('Y-m-d\TH:i');
    } catch (Throwable) {
        return '';
    }
}

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
