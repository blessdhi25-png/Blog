<?php
require_once __DIR__ . '/../includes/functions.php';

$perPage = 8;
$posts   = published_posts();
$pages   = max(1, (int)ceil(count($posts) / $perPage));
$page    = min($pages, max(1, (int)($_GET['page'] ?? 1)));
$slice   = array_slice($posts, ($page - 1) * $perPage, $perPage);

require __DIR__ . '/../includes/header.php';
?>
<section class="masthead">
  <h1><?= e(SITE_NAME) ?></h1>
  <p><?= e(SITE_TAGLINE) ?></p>
</section>

<?php if (!$slice): ?>
  <section class="empty">
    <h2>No posts yet</h2>
    <?php if (is_admin()): ?>
      <p><a class="button" href="<?= e(url('admin/edit.php')) ?>">Write the first post</a></p>
    <?php else: ?>
      <p>The first post is on its way. Check back soon.</p>
    <?php endif; ?>
  </section>
<?php else: ?>
  <ol class="entries">
    <?php foreach ($slice as $post): ?>
      <li class="entry">
        <time datetime="<?= e(substr($post['published_at'], 0, 10)) ?>"><?= e(format_date($post['published_at'])) ?></time>
        <div>
          <h2><a href="<?= e(url('post.php?slug=' . urlencode($post['slug']))) ?>"><?= e($post['title']) ?></a></h2>
          <p><?= e(post_excerpt($post)) ?></p>
          <span class="meta"><?= reading_time($post['body']) ?> min read</span>
        </div>
      </li>
    <?php endforeach; ?>
  </ol>

  <?php if ($pages > 1): ?>
    <nav class="pager" aria-label="Pages">
      <?php if ($page > 1): ?>
        <a href="<?= e(url('?page=' . ($page - 1))) ?>">Newer posts</a>
      <?php endif; ?>
      <span>Page <?= $page ?> of <?= $pages ?></span>
      <?php if ($page < $pages): ?>
        <a href="<?= e(url('?page=' . ($page + 1))) ?>">Older posts</a>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
