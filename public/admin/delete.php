<?php
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$id   = (string)($_GET['id'] ?? $_POST['id'] ?? '');
$post = find_post_by('id', $id);

if ($post === null) {
    set_flash('That post was already gone.');
    redirect('admin/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $remaining = array_filter(load_posts(), fn($p) => $p['id'] !== $id);
    if (save_posts($remaining)) {
        set_flash('Post deleted.');
    } else {
        set_flash('Could not delete the post. Make sure the data folder is writable.');
    }
    redirect('admin/');
}

$pageTitle = 'Delete post';
$bodyClass = 'admin';
require __DIR__ . '/../../includes/header.php';
?>
<section class="narrow">
  <h1>Delete this post?</h1>
  <p>&ldquo;<?= e($post['title']) ?>&rdquo; will be removed for good. This can't be undone.</p>
  <form method="post" class="form-actions">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e($post['id']) ?>">
    <button class="button danger" type="submit">Delete post</button>
    <a href="<?= e(url('admin/')) ?>">Keep post</a>
  </form>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
