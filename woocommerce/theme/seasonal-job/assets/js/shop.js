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
