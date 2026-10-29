<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

// Fallbacks for servers where the mbstring extension is not enabled.
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $text): int
    {
        return (int)preg_match_all('/./su', $text);
    }
}
if (!function_exists('mb_substr')) {
    function mb_substr(string $text, int $start, ?int $length = null): string
    {
        preg_match_all('/./su', $text, $m);
        return implode('', array_slice($m[0], $start, $length));
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

/* ---------- Basics ---------- */

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function set_flash(string $message): void
{
    $_SESSION['flash'] = $message;
}

function take_flash(): ?string
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $message;
}

/* ---------- Auth ---------- */

function admin_exists(): bool
{
    return is_file(ADMIN_FILE);
}

function is_admin(): bool
{
    return !empty($_SESSION['admin']);
}

function require_admin(): void
{
    if (!is_admin()) {
        redirect('admin/login.php');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = (string)($_POST['csrf'] ?? '');
    if (!hash_equals(csrf_token(), $sent)) {
        http_response_code(400);
        exit('This form expired. Go back, reload the page and try again.');
    }
}

/* ---------- Posts (stored in data/posts.json) ---------- */

function load_posts(): array
{
    if (!is_file(POSTS_FILE)) {
        return [];
    }
    $data = json_decode((string)file_get_contents(POSTS_FILE), true);
    return is_array($data) ? $data : [];
}

function save_posts(array $posts): bool
{
    if (!is_dir(DATA_DIR) && !mkdir(DATA_DIR, 0775, true)) {
        return false;
    }
    $json = json_encode(
        array_values($posts),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    return $json !== false && file_put_contents(POSTS_FILE, $json, LOCK_EX) !== false;
}

function published_posts(): array
{
    $posts = array_filter(load_posts(), fn($p) => ($p['status'] ?? '') === 'published');
    usort($posts, fn($a, $b) => strcmp($b['published_at'], $a['published_at']));
    return $posts;
}

function find_post_by(string $field, string $value): ?array
{
    foreach (load_posts() as $post) {
        if (($post[$field] ?? null) === $value) {
            return $post;
        }
    }
    return null;
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'post';
}

function unique_slug(string $base, ?string $ignoreId = null): string
{
    $taken = [];
    foreach (load_posts() as $post) {
        if ($ignoreId !== null && $post['id'] === $ignoreId) {
            continue;
        }
        $taken[$post['slug']] = true;
    }
    $slug = $base;
    $n = 2;
    while (isset($taken[$slug])) {
        $slug = $base . '-' . $n++;
    }
    return $slug;
}

/* ---------- Display helpers ---------- */

function format_date(string $iso): string
{
    $time = strtotime($iso);
    return $time ? date('j F Y', $time) : '';
}

function reading_time(string $body): int
{
    return max(1, (int)ceil(str_word_count(strip_tags($body)) / 220));
}

function post_excerpt(array $post): string
{
    if (trim($post['excerpt'] ?? '') !== '') {
        return $post['excerpt'];
    }
    $plain = trim(preg_replace('/[#>*\[\]\-]+|\(https?:[^)]*\)/', '', $post['body']) ?? '');
    $plain = preg_replace('/\s+/', ' ', $plain) ?? '';
    return mb_strlen($plain) > 170 ? rtrim(mb_substr($plain, 0, 170)) . '…' : $plain;
}

// Inline formatting. Text is escaped first, so only the tags below can appear.
function render_inline(string $text): string
{
    $text = e($text);
    $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text) ?? $text;
    $text = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $text) ?? $text;
    $text = preg_replace(
        '/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/',
        '<a href="$2" rel="noopener noreferrer">$1</a>',
        $text
    ) ?? $text;
    return $text;
}

// Turns plain text into HTML: paragraphs, ## headings, - lists, > quotes.
function render_body(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $out = '';
    foreach (preg_split('/\n{2,}/', trim($text)) ?: [] as $block) {
        $block = trim($block);
        if ($block === '') {
            continue;
        }
        if (preg_match('/^(#{2,3})\s+([^\n]+)$/', $block, $m)) {
            $level = strlen($m[1]);
            $out .= "<h$level>" . render_inline($m[2]) . "</h$level>\n";
            continue;
        }
        $lines = explode("\n", $block);
        if (!array_diff(array_map(fn($l) => (int)(strpos($l, '- ') === 0), $lines), [1])) {
            $out .= "<ul>\n";
            foreach ($lines as $line) {
                $out .= '<li>' . render_inline(substr($line, 2)) . "</li>\n";
            }
            $out .= "</ul>\n";
            continue;
        }
        if (strpos($block, '>') === 0) {
            $quote = implode("\n", array_map(fn($l) => ltrim(ltrim($l, '>')), $lines));
            $out .= '<blockquote><p>' . nl2br(render_inline($quote), false) . "</p></blockquote>\n";
            continue;
        }
        $out .= '<p>' . nl2br(render_inline($block), false) . "</p>\n";
    }
    return $out;
}
