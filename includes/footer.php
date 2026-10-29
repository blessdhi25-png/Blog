</main>
<footer class="site-footer">
  <div class="wrap">
    <p>&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>.
    <?php if (!is_admin()): ?>
      <a href="<?= e(url('admin/login.php')) ?>">Admin login</a>
    <?php endif; ?>
    </p>
  </div>
</footer>
</body>
</html>
