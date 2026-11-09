# WooCommerce shop (shop.benzilinskas.com)

The live shop runs on WordPress + WooCommerce + WooPayments in `public_html/__shop` on Hostinger. These files are the customisations; everything else (products, stock, shipping, payments) is managed in WordPress or the Woo mobile app.

## theme/seasonal-job/ → upload to `wp-content/themes/seasonal-job/`

The active theme: a child of Twenty Twenty-Four with the same design as the custom shop and benzilinskas.com. It has the left column with the menu, the two-column product grid with "X of 33 left", the product page with photo thumbnails and size buttons, the hang-tag box, and the "Next drop" signup.
- **Templates:** thin wrappers; `functions.php` draws the markup.
- **Add-to-cart, cart and checkout:** WooCommerce's own, so stock, WooPayments and the Woo app work as normal.
- **To undo:** activate Twenty Twenty-Four in Appearance → Themes.

## mu-plugins/ → upload to `wp-content/mu-plugins/`

Must-use plugins load automatically and can't be switched off by accident.

- `seasonal-job-shop.php`
  - **First Edition numbering:** as each order becomes paid, every piece gets its number, saved on the order line as "Edition number: No. 7 / 33". It shows in the order screen, the Woo app and the order emails. Write it on the card you pack. The runs are product 175 (tee, 33), 334 (trucker, 9) and 335 (cap, 22). Numbers count up per product and are never reused.
  - **Free shipping:** when an order gets free shipping (from $65), the paid $6 rate is hidden.
- **Drop list:** the `[sj_signup]` "Next drop" form saves emails to their own table. **WooCommerce → Drop list** shows them and downloads a CSV.
- `seasonal-job-style.php` + `seasonal-job-assets/`: a lighter restyle, used only if Twenty Twenty-Four itself is active again.

## backup-2026-11-08/

The products as they were before the First Edition setup: raw data, photos and a readable summary.

## Store setup done on Nov 8, 2026

- **Tee (product 175):** $40; stock S 4, M 10, L 12, XL 4, 2XL 3; 3XL removed.
- **Hats:** LITHUSA Trucker Hat (334), $32, 9 in stock; LITHUSA Cap (335), $30, 22 in stock.
- **Shipping:** US only; "United States" zone with $6 flat rate and free shipping from $65.
- **Payments:** WooPayments with Apple Pay and Google Pay; direct bank transfer off.
- **Visibility:** still in "Coming soon" mode, with the private preview link turned on.
- **Pages:** About, Size guide, Shipping and Returns created for the menu.
- **WooPayments express buttons** (WooPay, Apple Pay, Google Pay): "light-outline" theme, square corners, large size, so they sit under the black Add to cart button.
- **Printify Shipping Method plugin:** deactivated for the First Edition run, because it overrode the $6 rate. Turn it back on for Printify products.
- **Shop name:** "seasonal job" (site title, email sender, Coming soon page). Card statements show `SEASONAL JOB`. Order emails are styled black on off-white, the time zone is America/Chicago, and the tab icon is the LITHUSA flag.
- **Store address:** Fort Worth, TX 76179. WooCommerce calculates Texas sales tax, including on shipping, for Texas orders.
- **Shipping:**
  - US: $7, free from $65.
  - Lithuania and Europe: $20 + $5 per extra piece (Pirate Ship Simple Export Rate).
  - Rest of world: $27 + $8.
- **Privacy page:** rewritten. The old text is in `backup-2026-11-08/site/page-privacy-policy.html`.
- **Product template:** the customised WooCommerce single-product template was cleared so the theme's product page shows. The old version is saved in `backup-2026-11-08/site/single-product.html`.
