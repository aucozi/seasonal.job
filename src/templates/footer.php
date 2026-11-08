<footer class="site-footer">
  <div class="wrap footer-grid">
    <div class="footer-signup">
      <p class="meta">Next drop</p>
      <p>Edition 02 and new designs are coming, shipping to the US and Europe. Get the link a day early.</p>
      <form class="signup" method="post" action="<?= e(url('signup.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI'] ?? '/') ?>">
        <label class="sr-only" for="signup-email">Email</label>
        <input id="signup-email" type="email" name="email" required autocomplete="email" placeholder="you@email.com">
        <button class="btn" type="submit">Notify me</button>
      </form>
    </div>
    <nav class="footer-links" aria-label="Footer">
      <a href="<?= e(config('instagram_url')) ?>" rel="noopener">Instagram @seasonal.job</a>
      <a href="<?= e(url('page.php?p=shipping')) ?>">Shipping</a>
      <a href="<?= e(url('page.php?p=returns')) ?>">Returns</a>
      <a href="<?= e(url('page.php?p=size-guide')) ?>">Size guide</a>
      <a href="<?= e(url('page.php?p=privacy')) ?>">Privacy</a>
      <a href="https://benzilinskas.com">benzilinskas.com</a>
    </nav>
  </div>
  <div class="wrap fineprint meta">© <?= date('Y') ?> seasonal.job · Ships from the US</div>
</footer>
</body>
</html>
