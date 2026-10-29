<?php
require_once __DIR__ . '/../../includes/functions.php';
require_admin();

$id   = (string)($_GET['id'] ?? '');
$post = $id !== '' ? find_post_by('id', $id) : null;

if ($id !== '' && $post === null) {
    http_response_code(404);
    exit('Post not found.');
}

$form = $post ?? ['title' => '', 'slug' => '', 'excerpt' => '', 'body' => '', 'status' => 'draft'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $form['title']   = trim((string)($_POST['title'] ?? ''));
    $form['slug']    = trim((string)($_POST['slug'] ?? ''));
    $form['excerpt'] = trim((string)($_POST['excerpt'] ?? ''));
    $form['body']    = trim(str_replace("\r\n", "\n", (string)($_POST['body'] ?? '')));
    $form['status']  = ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft';

    if ($form['title'] === '') {
        $errors[] = 'Add a title.';
    } elseif (mb_strlen($form['title']) > 140) {
        $errors[] = 'Shorten the title to 140 characters or fewer.';
    }
    if ($form['body'] === '') {
        $errors[] = 'Write something in the body.';
    }
    if (mb_strlen($form['excerpt']) > 300) {
        $errors[] = 'Shorten the summary to 300 characters or fewer.';
    }

    if (!$errors) {
        $now   = date('c');
        $posts = load_posts();
        $slug  = unique_slug(slugify($form['slug'] !== '' ? $form['slug'] : $form['title']), $post['id'] ?? null);

        if ($post === null) {
            $posts[] = [
                'id'           => bin2hex(random_bytes(6)),
                'title'        => $form['title'],
                'slug'         => $slug,
                'excerpt'      => $form['excerpt'],
                'body'         => $form['body'],
                'status'       => $form['status'],
                'created_at'   => $now,
                'published_at' => $form['status'] === 'published' ? $now : '',
                'updated_at'   => $now,
            ];
        } else {
            foreach ($posts as $i => $existing) {
                if ($existing['id'] === $post['id']) {
                    $publishedAt = $existing['published_at'] ?? '';
                    if ($form['status'] === 'published' && $publishedAt === '') {
                        $publishedAt = $now;
                    }
                    $posts[$i] = array_merge($existing, [
                        'title'        => $form['title'],
                        'slug'         => $slug,
                        'excerpt'      => $form['excerpt'],
                        'body'         => $form['body'],
                        'status'       => $form['status'],
                        'published_at' => $publishedAt,
                        'updated_at'   => $now,
                    ]);
                }
            }
        }

        if (save_posts($posts)) {
            set_flash($form['status'] === 'published' ? 'Published.' : 'Draft saved.');
            redirect('admin/');
        }
        $errors[] = 'Could not save. Make sure the data folder is writable.';
    }
}

$pageTitle = $post ? 'Edit post' : 'New post';
$bodyClass = 'admin';
require __DIR__ . '/../../includes/header.php';
?>
<h1><?= e($pageTitle) ?></h1>

<?php if ($errors): ?>
  <div class="notice error" role="alert">
    <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<form method="post" class="form editor">
  <?= csrf_field() ?>

  <label for="title">Title</label>
  <input id="title" name="title" type="text" maxlength="140" required value="<?= e($form['title']) ?>">

  <label for="excerpt">Summary</label>
  <input id="excerpt" name="excerpt" type="text" maxlength="300" value="<?= e($form['excerpt']) ?>"
         aria-describedby="excerpt-help">
  <p class="help" id="excerpt-help">Shown on the home page. Leave empty to use the start of the post.</p>

  <label for="body">Body</label>
  <textarea id="body" name="body" rows="18" required aria-describedby="body-help"><?= e($form['body']) ?></textarea>
  <p class="help" id="body-help">
    Leave a blank line between paragraphs. Use <code>## Heading</code>, <code>**bold**</code>,
    <code>*italic*</code>, <code>[link text](https://example.com)</code>, <code>- list item</code>
    and <code>&gt; quote</code>.
  </p>

  <label for="slug">Web address</label>
  <input id="slug" name="slug" type="text" value="<?= e($form['slug']) ?>" aria-describedby="slug-help">
  <p class="help" id="slug-help">Leave empty to build it from the title.</p>

  <label for="status">Status</label>
  <select id="status" name="status">
    <option value="draft"<?= $form['status'] === 'draft' ? ' selected' : '' ?>>Draft (only you can see it)</option>
    <option value="published"<?= $form['status'] === 'published' ? ' selected' : '' ?>>Published</option>
  </select>

  <div class="form-actions">
    <button class="button" type="submit">Save post</button>
    <a href="<?= e(url('admin/')) ?>">Cancel</a>
  </div>
</form>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
