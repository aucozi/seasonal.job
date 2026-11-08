<p>First Edition orders are packed by hand and shipped from the US with USPS Ground Advantage, usually within 2 business days. Delivery takes about 3–7 business days after that.</p>
<ul>
  <li>Shipping is <?= money((int) config('shipping.flat_cents', 600)) ?> per order, and free on orders over <?= money((int) config('shipping.free_over_cents', 6500)) ?>.</li>
  <li>You'll get a tracking number by email when your order ships.</li>
  <li>We ship to US addresses only for now. Edition 02 will ship to Europe as well.</li>
</ul>
<p>Questions about an order? Email <a href="mailto:<?= e(config('mail.reply_to', config('mail.from'))) ?>"><?= e(config('mail.reply_to', config('mail.from'))) ?></a>.</p>
