# Webmaster tests

These tests were designed by Copilot.

These are standalone PHP regression scripts, not PHPUnit tests. Run them from
the `loopis-develooper` plugin directory; they stub the WordPress APIs and
database calls they need, so no WordPress installation or live database is
required. Fixtures use temporary files and directories and are cleaned up
after each run.

The tests require a sibling `loopis-webmaster` checkout. In addition,
`image-migration-directories.php` and `webp-metadata.php` load shared
functions from a sibling `loopis-content` checkout; `webp-metadata.php` also
needs PHP GD with WebP support. The migration test needs a writable temporary
directory and permission to create symbolic links.

Run the full suite with:

```sh
(
set -e
php tests/loopis-webmaster/image-migration-directories.php
php tests/loopis-webmaster/webp-metadata.php
for mode in render db-error stack revive partial invalid same-site denied; do
    php tests/loopis-webmaster/move-user.php "$mode"
done
php tests/loopis-webmaster/table-menu.php
for context in frontend unauthorized site-admin admin; do
    php tests/loopis-webmaster/plugin-loading.php "$context"
done
)
```

Each script can also be run separately. The `move-user.php` modes cover form
rendering, database errors, multisite restoration, partial failures, invalid
input, and access checks. The `plugin-loading.php` contexts check which tools
and menus load for frontend, unauthorized, site-admin, and super-admin
requests. A successful run prints a `... passed` message; failures throw an
exception and exit with an error.
