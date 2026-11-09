# benzilinskas.com (main WordPress site)

- `mu-plugins/disable-comments.php` → `public_html/wp-content/mu-plugins/` on Hostinger. Turns off comments, pingbacks and trackbacks site-wide because of spam. Existing comments are hidden, not deleted; the Comments screen is still reachable at `wp-admin/edit-comments.php?sj_show=1` to clear them. Delete the file to bring comments back.
