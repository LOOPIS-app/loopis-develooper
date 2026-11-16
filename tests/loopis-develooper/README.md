# Plugin tests

These standalone PHP regression tests were designed by Copilot. They use
WordPress stubs rather than a running WordPress installation or database, so
they complement rather than replace integration testing.

Run commands from the `loopis-develooper` plugin directory:

```sh
php tests/loopis-develooper/log-viewer.php
php tests/loopis-develooper/roles.php
php tests/loopis-develooper/sample-users.php
php tests/loopis-develooper/sample-posts.php
php tests/loopis-develooper/sample-post-images.php
php tests/loopis-develooper/sample-page.php success
php tests/loopis-develooper/sample-page.php failure
php tests/loopis-develooper/sample-page.php post-failure
php tests/loopis-develooper/sample-page.php both-failure
php tests/loopis-theme/post-forward.php
for context in frontend unauthorized site-admin super-no-cap admin; do
    php tests/loopis-develooper/plugin-loading.php "$context"
done
```

For Admin, check out `loopis-admin` alongside `loopis-develooper`, then run:

```sh
(
set -e
for context in frontend mainsite subsite; do
    php tests/loopis-admin/plugin-loading.php "$context"
done
)
```

This checks frontend isolation, site-specific loading, common hooks, styles,
and menu setup using WordPress stubs.

For the Webmaster suite, see [`loopis-webmaster/README.md`](../loopis-webmaster/README.md).
Those tests require a sibling `loopis-webmaster` checkout, and some also need
`loopis-content` and PHP GD with WebP support.
