<?php
require_once __DIR__ . '/../../includes/functions.php';

if (is_admin()) {
    redirect('admin/');
}

$setup = !admin_exists();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $password = (string)($_POST['password'] ?? '');

    if ($setup) {
        $confirm = (string)($_POST['confirm'] ?? '');
        if (strlen($password) < 8) {
            $error = 'Use at least 8 characters.';
        } elseif ($password !== $confirm) {
            $error = "The two passwords don't match.";
        } else {
            if (!is_dir(DATA_DIR)) {
                mkdir(DATA_DIR, 0775, true);
            }
            $saved = file_put_contents(
                ADMIN_FILE,
                json_encode(['password_hash' => password_hash($password, PASSWORD_DEFAULT)]),
                LOCK_EX
            );
            if ($saved === false) {
                $error = 'Could not save the password. Make sure the data folder is writable.';
            } else {
                session_regenerate_id(true);
                $_SESSION['admin'] = true;
                set_flash('Password set. You are logged in.');
                redirect('admin/');
            }
        }
    } else {
        $data = json_decode((string)file_get_contents(ADMIN_FILE), true);
        if (is_array($data) && password_verify($password, (string)($data['password_hash'] ?? ''))) {
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            redirect('admin/');
        }
        usleep(500000); // slows down password guessing
        $error = 'That password is not correct.';
    }
}

$pageTitle = $setup ? 'Create your admin password' : 'Log in';
$bodyClass = 'admin';
require __DIR__ . '/../../includes/header.php';
?>
<section class="narrow">
  <h1><?= $setup ? 'Create your admin password' : 'Log in' ?></h1>
  <?php if ($setup): ?>
    <p>This is the first run. Choose a password for the admin area.</p>
  <?php endif; ?>
  <?php if ($error): ?><p class="notice error" role="alert"><?= e($error) ?></p><?php endif; ?>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <label for="password">Password</label>
    <input id="password" name="password" type="password" required autofocus
           autocomplete="<?= $setup ? 'new-password' : 'current-password' ?>">
    <?php if ($setup): ?>
      <label for="confirm">Repeat password</label>
      <input id="confirm" name="confirm" type="password" required autocomplete="new-password">
    <?php endif; ?>
    <button class="button" type="submit"><?= $setup ? 'Save password' : 'Log in' ?></button>
  </form>
</section>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
