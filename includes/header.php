<?php
$pageTitle = $pageTitle ?? null;
$bodyClass = $bodyClass ?? '';
$fullTitle = $pageTitle ? $pageTitle . ' | ' . SITE_NAME : SITE_NAME;
$flash = take_flash();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($fullTitle) ?></title>
  <meta name="description" content="<?= e($metaDescription ?? SITE_TAGLINE) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700&family=Source+Serif+4:ital,opsz,wght@0,8..60,400;0,8..60,600;1,8..60,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<a class="skip" href="#main">Skip to content</a>
<header class="site-header">
  <div class="wrap bar">
    <a class="brand" href="<?= e(url()) ?>"><?= e(SITE_NAME) ?></a>
    <?php if (is_admin()): ?>
      <nav class="admin-nav" aria-label="Admin">
        <a href="<?= e(url('admin/')) ?>">Posts</a>
        <a href="<?= e(url('admin/edit.php')) ?>">New post</a>
        <form method="post" action="<?= e(url('admin/logout.php')) ?>">
          <?= csrf_field() ?>
          <button type="submit" class="link-button">Log out</button>
        </form>
      </nav>
    <?php endif; ?>
  </div>
</header>
<main id="main" class="wrap">
<?php if ($flash): ?>
  <p class="notice" role="status"><?= e($flash) ?></p>
<?php endif; ?>
