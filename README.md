# seasonal.job shop

The shop at shop.benzilinskas.com. Phase 1 sells the LITHUSA First Edition (33 tees, 9 trucker hats, 22 caps) from US stock: Stripe Checkout, stock per size, numbered cards, order emails, an admin page, and a "next drop" email list. Plain PHP 8 and MySQL, built for Hostinger shared hosting with no extra services.

## How an order works

1. The customer adds pieces to the cart and clicks **Check out**.
2. The shop takes the pieces out of stock and sends the customer to Stripe. They are held for 30 minutes.
3. Stripe tells the shop the order is paid (`webhook.php`). The shop gives each piece its edition number and emails you and the customer.
4. If the customer doesn't pay, Stripe expires the checkout and the pieces go back into stock.
5. You pack the order with its numbered card. Then you mark it shipped in `/admin/` with the USPS tracking number, and the customer is emailed the tracking link.

## Set up on Hostinger (one time)

1. **Subdomain:** in hPanel go to Domains, then Subdomains, and create `shop`. Turn on SSL for it.
2. **Database:** in hPanel go to Databases, then MySQL Databases. Create a database and a user, and note the name, user and password.
3. **Upload:** upload this project into the subdomain's folder with File Manager or FTP.
   - If hPanel lets you point the subdomain at the `public/` folder, do that.
   - If it doesn't, the `.htaccess` in the project root sends visitors to `public/` and blocks the rest.
4. **Config:** copy `config.example.php` to `config.php` and fill in:
   - the database details
   - an `admin_password` (12+ characters)
   - the email addresses (create `shop@benzilinskas.com` under hPanel, then Emails)
5. **Stripe:**
   1. In the Stripe Dashboard, go to Developers, then API keys, and copy the **test** secret key (`sk_test_...`) into `config.php`.
   2. Under Developers, then Webhooks, add the endpoint `https://shop.benzilinskas.com/webhook.php` with these events:
      - `checkout.session.completed`
      - `checkout.session.async_payment_succeeded`
      - `checkout.session.async_payment_failed`
      - `checkout.session.expired`
   3. Copy its signing secret (`whsec_...`) into `config.php`.
6. **Install:** open `https://shop.benzilinskas.com/admin/`, log in, and click **Install database**.
7. **Test:** place an order with Stripe's test card `4242 4242 4242 4242`. Check that both emails arrive and the order shows in `/admin/`.
8. **Launch day:**
   1. Swap in the **live** secret key.
   2. Add a live-mode webhook with the same URL and events, and use its signing secret.
   3. In `/admin/` under Stock, set the counts back to the real numbers.

## Photos

All product photos are in `public/assets/img/` and listed per product in `src/catalog.php` (`image` is the main photo, `gallery` the rest). The hat and tag photos are composited onto a light backdrop so black hats stay visible on the dark site. `og.jpg` is the link preview on Instagram and iMessage.

One placeholder is left: `hero.svg`, the big image at the top of the home page. Use a wide photo (e.g. 2400×1350) and change the filename in `public/index.php`.

To change photos after the database is installed, edit the `image` and `gallery` columns of the `products` table in phpMyAdmin. `gallery` is a comma-separated list of files.

## Selling outside the site

Sold a piece at an event or in a DM? Lower its count under **Stock** in `/admin/` straight away, so the site never sells something you no longer have.

## Running the tests

```
php tests/run.php
```

This uses a throwaway SQLite database and a local PHP server. It covers stock reservation, overselling, edition numbers, Stripe webhook signatures and expiry, emails, the drop list, and the admin page. No Stripe account is needed.
