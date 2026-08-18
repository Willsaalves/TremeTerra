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

$db = getDb();

$id       = isset($_POST['id']) ? (int) $_POST['id'] : null;
$title    = trim((string) ($_POST['title'] ?? ''));
$slug     = slugify(trim((string) ($_POST['slug'] ?? '')) ?: $title);
$category = trim((string) ($_POST['category'] ?? ''));
$seoTitle = trim((string) ($_POST['seo_title'] ?? ''));
$seoDescription = trim((string) ($_POST['seo_description'] ?? ''));
$directAnswer   = trim((string) ($_POST['direct_answer'] ?? ''));
$coverImageUrl  = trim((string) ($_POST['cover_image_url'] ?? ''));
$coverImageAlt  = trim((string) ($_POST['cover_image_alt'] ?? ''));
$bodyHtml       = (string) ($_POST['body_html'] ?? '');
$status         = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';

if ($title === '' || $slug === '') {
    http_response_code(422);
    exit('Título e slug são obrigatórios.');
}

$questions = $_POST['faq_question'] ?? [];
$answers   = $_POST['faq_answer'] ?? [];
$faqItems = [];
if (is_array($questions) && is_array($answers)) {
    foreach ($questions as $i => $question) {
        $question = trim((string) $question);
        $answer = trim((string) ($answers[$i] ?? ''));
        if ($question !== '' && $answer !== '') {
            $faqItems[] = ['question' => $question, 'answer' => $answer];
        }
    }
}
$faqJson = $faqItems === [] ? null : json_encode($faqItems, JSON_UNESCAPED_UNICODE);

$now = gmdate('Y-m-d H:i:s');

try {
    if ($id !== null) {
        $existing = $db->prepare('SELECT status, published_at FROM posts WHERE id = ?');
        $existing->execute([$id]);
        $row = $existing->fetch();
        if ($row === false) {
            http_response_code(404);
            exit('Post não encontrado.');
        }

        $publishedAt = $row['published_at'];
        if ($status === 'published' && $publishedAt === null) {
            $publishedAt = $now;
        }

        $stmt = $db->prepare('
            UPDATE posts SET
                title = ?, slug = ?, category = ?, seo_title = ?, seo_description = ?,
                direct_answer = ?, cover_image_url = ?, cover_image_alt = ?, body_html = ?,
                faq_json = ?, status = ?, published_at = ?, updated_at = ?
            WHERE id = ?
        ');
        $stmt->execute([
            $title, $slug, $category, $seoTitle, $seoDescription,
            $directAnswer, $coverImageUrl, $coverImageAlt, $bodyHtml,
            $faqJson, $status, $publishedAt, $now, $id,
        ]);
    } else {
        $publishedAt = $status === 'published' ? $now : null;

        $stmt = $db->prepare('
            INSERT INTO posts (
                title, slug, category, seo_title, seo_description, direct_answer,
                cover_image_url, cover_image_alt, body_html, faq_json, status,
                published_at, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $title, $slug, $category, $seoTitle, $seoDescription, $directAnswer,
            $coverImageUrl, $coverImageAlt, $bodyHtml, $faqJson, $status,
            $publishedAt, $now, $now,
        ]);
    }
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'UNIQUE')) {
        http_response_code(422);
        exit('Já existe um post com essa URL (slug). Escolha outra.');
    }
    throw $e;
}

header('Location: /admin/index.php');
exit;
