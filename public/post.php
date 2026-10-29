<?php
require_once __DIR__ . '/../includes/functions.php';

$post = find_post_by('slug', (string)($_GET['slug'] ?? ''));
$visible = $post && ($post['status'] === 'published' || is_admin());

if (!$visible) {
    http_response_code(404);
    $pageTitle = 'Post not found';
    require __DIR__ . '/../includes/header.php';
    ?>
    <section class="empty">
      <h1>That post doesn't exist</h1>
      <p>It may have been moved or removed. <a href="<?= e(url()) ?>">Go to all posts</a>.</p>
    </section>
    <?php
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$pageTitle = $post['title'];
$metaDescription = post_excerpt($post);
require __DIR__ . '/../includes/header.php';
?>
<article class="post">
  <?php if ($post['status'] !== 'published'): ?>
    <p class="notice">This is a draft. Only you can see it.
      <a href="<?= e(url('admin/edit.php?id=' . urlencode($post['id']))) ?>">Edit post</a></p>
  <?php endif; ?>
  <a class="back" href="<?= e(url()) ?>">All posts</a>
  <h1><?= e($post['title']) ?></h1>
  <p class="meta">
    <?php if ($post['published_at']): ?>
      <time datetime="<?= e(substr($post['published_at'], 0, 10)) ?>"><?= e(format_date($post['published_at'])) ?></time>,
    <?php endif; ?>
    <?= reading_time($post['body']) ?> min read
  </p>
  <div class="prose"><?= render_body($post['body']) ?></div>
</article>
<?php require __DIR__ . '/../includes/footer.php'; ?>
