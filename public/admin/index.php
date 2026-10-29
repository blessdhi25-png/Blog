<?php
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$posts = load_posts();
usort($posts, fn($a, $b) => strcmp($b['updated_at'], $a['updated_at']));

$pageTitle = 'Posts';
$bodyClass = 'admin';
require __DIR__ . '/../../includes/header.php';
?>
<div class="admin-head">
  <h1>Posts</h1>
  <a class="button" href="<?= e(url('admin/edit.php')) ?>">New post</a>
</div>

<?php if (!$posts): ?>
  <section class="empty">
    <h2>Nothing written yet</h2>
    <p>Posts you save will be listed here, drafts included.</p>
    <p><a class="button" href="<?= e(url('admin/edit.php')) ?>">Write your first post</a></p>
  </section>
<?php else: ?>
  <div class="table-scroll">
    <table class="posts-table">
      <thead>
        <tr><th>Title</th><th>Status</th><th>Last saved</th><th><span class="sr-only">Actions</span></th></tr>
      </thead>
      <tbody>
      <?php foreach ($posts as $post): ?>
        <tr>
          <td><?= e($post['title']) ?></td>
          <td><span class="status <?= e($post['status']) ?>"><?= $post['status'] === 'published' ? 'Published' : 'Draft' ?></span></td>
          <td><?= e(format_date($post['updated_at'])) ?></td>
          <td class="actions">
            <a href="<?= e(url('admin/edit.php?id=' . urlencode($post['id']))) ?>">Edit</a>
            <a href="<?= e(url('post.php?slug=' . urlencode($post['slug']))) ?>">View</a>
            <a href="<?= e(url('admin/delete.php?id=' . urlencode($post['id']))) ?>">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
