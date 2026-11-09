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

  // Variation dropdowns become buttons showing what's left in each size. No size is chosen for the
  // buyer: "Add to cart" asks them to pick one first. A dropdown with a single choice (Colors: Black)
  // is chosen and hidden.
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
        wrap.appendChild(button);
      });
      select.classList.add('sj-replaced');
      select.parentNode.insertBefore(wrap, select);
      set(select, '');
    });

    // "Add to cart" without a size: point at the sizes instead of WooCommerce's pop-up alert.
    var hint = document.createElement('p');
    hint.className = 'sj-size-hint';
    hint.setAttribute('role', 'alert');
    hint.hidden = true;
    hint.textContent = 'Choose your size first.';
    var sizes = form.querySelector('.sj-sizes');
    if (sizes) { sizes.parentNode.insertBefore(hint, sizes.nextSibling); }
    form.addEventListener('click', function (e) {
      if (!e.target.closest('.single_add_to_cart_button')) { return; }
      var missing = Array.prototype.some.call(selects, function (sel) { return sel.value === ''; });
      if (!missing) { return; }
      e.preventDefault();
      e.stopPropagation();
      e.target.closest('.single_add_to_cart_button').blur(); // no focus ring left on the button
      hint.hidden = false;
      if (sizes) { sizes.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
    }, true);
    form.addEventListener('change', function () { hint.hidden = true; });
  });
  }
  // Run after WooCommerce has set up its variation form on page load.
  if ($) { $(function () { setTimeout(initSizes, 0); }); } else { document.addEventListener('DOMContentLoaded', initSizes); }
})();

/* Fixed bar on phones, like speeed.co: always reachable while scrolling the photos and description,
   hidden while the real button is on screen. For the tee it scrolls up to the sizes, so nobody ends
   up with a size they didn't pick; one-size hats go straight into the cart. */
(function () {
  var buy = document.querySelector('[data-sj-buy]');
  var real = buy && buy.querySelector('.single_add_to_cart_button');
  if (!real) { return; }
  var hasSizes = !!buy.querySelector('form.variations_form');
  var bar = document.createElement('div');
  bar.className = 'sj-sticky-buy';
  bar.innerHTML = '<button type="button" class="sj-button"></button>';
  var button = bar.firstChild;
  button.textContent = (hasSizes ? 'Choose size · ' : 'Add to cart · ') + buy.getAttribute('data-price');
  button.addEventListener('click', function () {
    if (!hasSizes) { real.click(); return; }
    var sizes = buy.querySelector('.sj-sizes') || buy;
    sizes.scrollIntoView({ behavior: 'smooth', block: 'center' });
    sizes.classList.remove('sj-flash');
    void sizes.offsetWidth; // restart the highlight
    sizes.classList.add('sj-flash');
  });
  document.body.appendChild(bar);
  document.body.classList.add('has-sj-sticky-buy');
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (entries) {
      bar.classList.toggle('is-hidden', entries[0].isIntersecting);
    }).observe(buy);
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

/* Checkout opens with "Use same address for billing" ticked. WooCommerce unticks it when the saved
   billing and shipping addresses differ, e.g. after someone switched the shipping country and came
   back. Most people bill to where they ship; unticking it is still one tap away. */
(function () {
  if (!document.querySelector('.wp-block-woocommerce-checkout')) { return; }
  var tries = 0;
  var timer = setInterval(function () {
    var box = document.querySelector('.wc-block-checkout__use-address-for-billing input[type=checkbox]');
    if (box || ++tries > 60) { clearInterval(timer); }
    if (box && !box.checked) { box.click(); }
  }, 250);
})();
