/* seasonal job theme: product photo thumbnails and size buttons. */
(function () {
  // Thumbnails swap the main photo; without JS they link to the full image.
  document.querySelectorAll('[data-gallery]').forEach(function (gallery) {
    var main = gallery.querySelector('[data-main]');
    gallery.querySelectorAll('[data-thumb]').forEach(function (thumb) {
      thumb.addEventListener('click', function (event) {
        event.preventDefault();
        main.removeAttribute('srcset');
        main.src = thumb.getAttribute('href');
        gallery.querySelectorAll('[data-thumb]').forEach(function (t) { t.removeAttribute('aria-current'); });
        thumb.setAttribute('aria-current', 'true');
      });
    });
  });

  // Variation dropdowns become buttons showing what's left in each size.
  // A dropdown with a single choice (Colors: Black) is chosen and hidden.
  var $ = window.jQuery;
  function initSizes() {
  document.querySelectorAll('form.variations_form:not([data-sj-sizes])').forEach(function (form) {
    form.setAttribute('data-sj-sizes', '1');
    var variations = [];
    try { variations = JSON.parse(form.getAttribute('data-product_variations') || '[]'); } catch (e) { variations = []; }
    var selects = form.querySelectorAll('table.variations select');

    function set(select, value) {
      if ($) { $(select).val(value).trigger('change'); } else { select.value = value; select.dispatchEvent(new Event('change', { bubbles: true })); }
    }

    selects.forEach(function (select) {
      var options = Array.prototype.filter.call(select.options, function (o) { return o.value !== ''; });
      var row = select.closest('tr');
      if (options.length === 1) {
        set(select, options[0].value);
        if (row) { row.classList.add('sj-hidden'); }
        return;
      }
      var attr = select.getAttribute('name');
      var wrap = document.createElement('div');
      wrap.className = 'sj-sizes';
      wrap.setAttribute('role', 'group');
      var first = null;
      options.forEach(function (option) {
        var match = variations.filter(function (v) { return v.attributes && v.attributes[attr] === option.value; })[0];
        var left = match ? (match.max_qty === '' || match.max_qty == null ? null : parseInt(match.max_qty, 10)) : null;
        var inStock = match ? match.is_in_stock && (left === null || left > 0) : false;
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'sj-size';
        button.setAttribute('aria-pressed', 'false');
        button.innerHTML = option.textContent + '<small>' + (inStock ? (left !== null ? left + ' left' : 'in stock') : 'gone') + '</small>';
        button.disabled = !inStock;
        button.addEventListener('click', function () {
          wrap.querySelectorAll('.sj-size').forEach(function (b) { b.setAttribute('aria-pressed', 'false'); });
          button.setAttribute('aria-pressed', 'true');
          set(select, option.value);
        });
        if (inStock && !first) { first = button; }
        wrap.appendChild(button);
      });
      select.classList.add('sj-replaced');
      select.parentNode.insertBefore(wrap, select);
      if (first) { first.click(); }
    });
  });
  }
  // Run after WooCommerce has set up its variation form on page load.
  if ($) { $(function () { setTimeout(initSizes, 0); }); } else { document.addEventListener('DOMContentLoaded', initSizes); }
})();

/* Fixed "Add to cart" bar on phones, like speeed.co: always reachable while scrolling the photos and
   description; hidden while the real button is on screen. It presses the real button, so the
   chosen size and WooCommerce's checks still apply. */
(function () {
  var buy = document.querySelector('[data-sj-buy]');
  var real = buy && buy.querySelector('.single_add_to_cart_button');
  if (!real || !window.matchMedia) { return; }
  var bar = document.createElement('div');
  bar.className = 'sj-sticky-buy';
  bar.innerHTML = '<button type="button" class="sj-button"></button>';
  var button = bar.firstChild;
  var sizeSelect = buy.querySelector('select[name="attribute_sizes"]');
  function label() {
    var size = sizeSelect && sizeSelect.value ? ' · ' + sizeSelect.value : '';
    button.textContent = 'Add to cart · ' + buy.getAttribute('data-price') + size;
  }
  label();
  if (sizeSelect) { sizeSelect.addEventListener('change', label); if (window.jQuery) { window.jQuery(sizeSelect).on('change', label); } }
  button.addEventListener('click', function () { real.click(); });
  document.body.appendChild(bar);
  document.body.classList.add('has-sj-sticky-buy');
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (entries) {
      bar.classList.toggle('is-hidden', entries[0].isIntersecting);
    }).observe(real);
  }
})();

/* "Added to cart" panel: close with ×, "Keep shopping", Escape or a click outside it. */
(function () {
  var added = document.querySelector('[data-sj-added]');
  if (!added) { return; }
  function close() { added.parentNode && added.parentNode.removeChild(added); }
  added.querySelectorAll('[data-sj-added-close]').forEach(function (b) { b.addEventListener('click', close); });
  added.addEventListener('click', function (e) { if (e.target === added) { close(); } });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { close(); } });
  // Move focus to the panel itself (not the Checkout button, which would draw a focus ring around it).
  var panel = added.querySelector('.sj-added-panel');
  if (panel) { panel.setAttribute('tabindex', '-1'); panel.focus({ preventScroll: true }); }
})();

/* Next drop signup without leaving the page: the form is replaced by the confirmation. */
(function () {
  document.querySelectorAll('form[data-sj-signup]').forEach(function (form) {
    var msg = form.parentNode.querySelector('.sj-signup-msg');
    form.addEventListener('submit', function (e) {
      if (!window.fetch || !window.FormData) { return; }
      e.preventDefault();
      var button = form.querySelector('button');
      button.disabled = true;
      button.textContent = 'Adding…';
      var data = new FormData(form);
      data.append('ajax', '1');
      // getAttribute, because form.action is the hidden <input name="action">, not the URL.
      fetch(form.getAttribute('action'), { method: 'POST', body: data, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          msg.textContent = res.message;
          msg.hidden = false;
          msg.classList.toggle('is-error', res.status === 'bad');
          msg.classList.toggle('is-done', res.status !== 'bad');
          if (res.status === 'bad') {
            button.disabled = false;
            button.textContent = 'Notify me';
          } else {
            form.hidden = true;
          }
        })
        .catch(function () { form.submit(); });
    });
  });
})();
