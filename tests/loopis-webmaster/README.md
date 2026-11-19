# Webmaster tests

These tests were designed by Copilot.

These are standalone PHP regression scripts, not PHPUnit tests. Run them from
the `loopis-develooper` plugin directory; they stub the WordPress APIs and
database calls they need, so no WordPress installation or live database is
required. Fixtures use temporary files and directories and are cleaned up
after each run.

The tests require a sibling `loopis-webmaster` checkout. The conversion and
WebP metadata tests also load functions from a sibling `loopis-content`
checkout and require PHP GD with WebP support. Both image tool tests need a
writable temporary directory; the conversion test also creates symbolic links.
The scan form regression test requires Node.js.

Run the full suite with:

```sh
(
set -e
php tests/loopis-webmaster/image-conversion.php
php tests/loopis-webmaster/image-cleanup.php
php tests/loopis-webmaster/image-log-path.php
php tests/loopis-webmaster/image-log-path.php custom
php tests/loopis-webmaster/image-batched-scan.php
node tests/loopis-webmaster/image-scan-form.js
node tests/loopis-webmaster/image-process-status.js
node tests/loopis-webmaster/image-cleanup-preview.js
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

The image conversion test covers naming, scan and queue isolation, safe
year-directory discovery, month-file pagination, WebP settings, logging and
legacy queue upgrades, including bounded filename planning for encoded titles.
The cleanup test covers review,
shared-image references, quarantine rollback, attachment backup files, and
independent log and queue resets. They share `image-test-helpers.php` for
WordPress stubs and fixtures. Cleanup still loads the conversion functions
because the current plugin uses shared filesystem, logging and locking helpers
from that tool. The log path test covers default and configured directories,
site-specific filenames, private permissions and untouched previous logs.
The batched scan test covers selected months, month boundaries, queue readiness,
cross-batch naming collisions, site-wide references, interrupted scans and
conversion file totals including thumbnails and skipped attachments.
The scan form test checks the submitted year and month and resume requests for both tools.
It also checks reporting of unexpected HTML server responses.
The status tests check completed scans and active processing on both pages.
The cleanup preview tests cover upload-relative paths, safe URLs and on-demand
preview interactions.
These stubs do not replace testing the SQL, media hooks and
editing race on a representative WordPress staging site.
