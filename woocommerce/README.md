# WooCommerce shop (shop.benzilinskas.com)

The live shop runs on WordPress + WooCommerce + WooPayments in `public_html/__shop` on Hostinger. These files are the customisations; everything else (products, stock, shipping, payments) is managed in WordPress or the Woo mobile app.

## theme/seasonal-job/ → upload to `wp-content/themes/seasonal-job/`

The active theme: a child of Twenty Twenty-Four with the same design as the custom shop and benzilinskas.com. It has the left column with the menu, the two-column product grid with "X of 33 left", the product page with photo thumbnails and size buttons, the hang-tag box, and the "Next drop" signup.
- **Templates:** thin wrappers; `functions.php` draws the markup.
- **Add-to-cart, cart and checkout:** WooCommerce's own, so stock, WooPayments and the Woo app work as normal. On top of that:
  - an "Added to cart" panel (bottom sheet on phones, card top right on desktop) with Checkout / View cart / Keep shopping;
  - no size is pre-selected: Add to cart without a size shows "Choose your size first.";
  - a fixed bar on phones while the buy section is off screen: "Choose size · $40" scrolls up to the sizes on the tee, "Add to cart · $30" adds the one-size hats;
  - checkout always opens with "Use same address for billing" ticked;
  - our own `page-checkout`, `page-cart` and `order-confirmation` templates, so these pages keep the left column and margins, with a "What happens next" list after ordering;
  - attribute labels shown singular ("Size: M", not "Sizes: M").
- **Order emails:** `woocommerce/emails/email-header.php` and `email-footer.php`, plus the `woocommerce_email_styles` filter in `functions.php`, give every WooCommerce email the shop's look: the "seasonal job" header, paper background, Albura with Georgia as fallback, italic headings, thin black rules, and Instagram/Spotify links in the footer. Subjects, headings and closing text are set in WooCommerce → Settings → Emails.
- **To undo:** activate Twenty Twenty-Four in Appearance → Themes.

## mu-plugins/ → upload to `wp-content/mu-plugins/`

Must-use plugins load automatically and can't be switched off by accident.

- `seasonal-job-shop.php`
  - **First Edition numbering:** as each order becomes paid, every piece gets its number, saved on the order line as "Edition number: No. 7 / 33". It shows in the order screen, the Woo app and the order emails. Write it on the card you pack. The runs are product 175 (tee, 33), 334 (trucker, 9) and 335 (cap, 22). Numbers count up per product and are never reused.
  - **Free shipping:** when an order gets free shipping (from $65), the paid $7 rate is hidden.
  - **Delivery estimate** under each shipping option ("Arrives Nov 11–17"): US 3–7 business days, Lithuania and Europe 1–3 weeks, rest of the world 1–4 weeks, counted from today in Texas time.
  - **Shipping waits for the address:** the country defaults to the US, but no rate (and no "free shipping") shows until an address is entered. Countries without states (Lithuania, most of Europe) don't ask for one; WooCommerce's default asked, so their shipping never calculated.
  - **Pay in Person is app-only:** the cash-on-delivery gateway titled "Pay in Person" stays on for Woo app sales but is hidden from the website cart and checkout.
  - **Drop list:** the `[sj_signup]` "Next drop" form saves emails to their own table and sends a short branded "You're on the list" email. It confirms on the page without reloading. The checkout has an "Email me before the next drop" box; ticked, the buyer joins the list once the order is paid. **WooCommerce → Drop list** shows the list and downloads a CSV.
  - **REST (shop managers, Application Password):** `GET/POST /wp-json/sj/v1/editions` reads or sets the next edition number per product, e.g. `{"next":{"175":1}}` to reset after a test order. `GET /wp-json/sj/v1/signups` lists the drop list, and `DELETE /wp-json/sj/v1/signups?email=…` takes someone off.
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
