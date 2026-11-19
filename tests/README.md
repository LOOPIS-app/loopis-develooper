# LOOPIS tests

This folder is the central home for tests run from the `loopis-develooper`
plugin directory. Coverage is a work in progress: more tests are planned for
LOOPIS plugins, mu-plugins and themes.

## Current coverage

- **Develooper:** plugin loading, logs, roles, sample users and posts, and
  sample images. See [test commands](./loopis-develooper/README.md).
- **Admin:** plugin loading across frontend, main-site and subsite contexts.
  See [test commands](./loopis-develooper/README.md#plugin-tests).
- **Webmaster:** plugin loading, image conversion and cleanup, WebP metadata, Move User
  and the table editor. See [test commands and prerequisites](./loopis-webmaster/README.md).
- **Theme:** forwarded-post image reuse and gift-form image selection. See
  [test commands](./loopis-develooper/README.md#plugin-tests).

There are currently no mu-plugin tests in this folder.

The current tests are standalone PHP regression scripts, designed by Copilot.
They use WordPress stubs rather than a running WordPress installation or
database, so they do not replace integration testing. Some suites require
sibling component checkouts or additional PHP extensions; see their
instructions for details.

## Recommended usage

Run the relevant tests locally while developing and before submitting changes.
They can also run in CI on a PHP environment with the required sibling
checkouts and extensions. Run the broader suites before releases.

These scripts use temporary fixtures rather than live site data; they are not
intended to test production directly. A passing result does not verify real
WordPress, database, browser or hosting behavior. Complement them with
integration and manual checks on a representative local or staging site
before deploying to production.
