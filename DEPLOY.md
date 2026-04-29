# Deploy notes

Production URL currently runs behind nginx/Apache-style hosting.

If every URL returns generic:

```text
500 Internal Server Error
The server encountered an internal error or misconfiguration
```

then Apache is rejecting `.htaccess` before PHP starts.

Use this order:

1. Upload the current minimal `.htaccess` files.
2. If 500 remains, delete both files on production:
   - `.htaccess`
   - `public/.htaccess`
3. Open:
   - `https://se.ifmo.ru/~s409784/vkus./health.php`
   - `https://se.ifmo.ru/~s409784/vkus./public/index.php?health=1`

The application does not require rewrite rules. It works through:

```text
public/index.php?route=menu
public/index.php?route=register
```

For SQLite, make `database` writable if possible:

```bash
chmod 775 database
chmod 664 database/app.sqlite 2>/dev/null || true
```

If `database` is not writable, the app falls back to PHP temp dir.
