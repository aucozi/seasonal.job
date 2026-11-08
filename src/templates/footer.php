    <section class="signup-block" aria-labelledby="signup-title">
      <h2 id="signup-title">Next drop</h2>
      <p>Edition 02 and new designs are coming, shipping to the US and Europe. Get the link a day early.</p>
      <form class="signup" method="post" action="<?= e(url('signup.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI'] ?? '/') ?>">
        <label class="sr-only" for="signup-email">Email</label>
        <input id="signup-email" type="email" name="email" required autocomplete="email" placeholder="your@email.com">
        <button class="btn" type="submit">Notify me</button>
      </form>
    </section>
  </main>
  <footer class="sidebar-foot">
    <ul class="social">
      <li><a href="<?= e(config('instagram_url', 'https://instagram.com/seasonal.job')) ?>" rel="noopener"><?php render('icons/instagram'); ?><span>Seasonal Job</span></a></li>
      <li><a href="<?= e(config('spotify_url', 'https://open.spotify.com/artist/2Cdr4xt4kY61ECVbILaAFp')) ?>" rel="noopener"><?php render('icons/spotify'); ?><span>Spotify</span></a></li>
    </ul>
    <p class="footer-links">
      <a href="<?= e(url('page.php?p=returns')) ?>">Returns</a>
      <a href="<?= e(url('page.php?p=privacy')) ?>">Privacy</a>
    </p>
    <p class="copyright">Ben Zilinskas / Seasonal Job All rights reserved © <?= date('Y') ?></p>
  </footer>
</div>
<script>
  // Product photo thumbnails swap the main photo; without JS they link to the full image.
  document.querySelectorAll('[data-gallery]').forEach(function (gallery) {
    var main = gallery.querySelector('[data-main]');
    gallery.querySelectorAll('[data-thumb]').forEach(function (thumb) {
      thumb.addEventListener('click', function (event) {
        event.preventDefault();
        main.src = thumb.getAttribute('href');
        gallery.querySelectorAll('[data-thumb]').forEach(function (t) { t.removeAttribute('aria-current'); });
        thumb.setAttribute('aria-current', 'true');
      });
    });
  });
</script>
</body>
</html>
