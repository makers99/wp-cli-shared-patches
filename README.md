=== wp-cli-db-export-clean ===
Contributors: makers99
Tags: wp-cli, woocommerce
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Adds the WP-CLI command `wp db export-clean` to create a MySQL database dump
without sensitive data related to customers and API secrets/credentials, while
retaining all administrative users and their related data.

Revisions are excluded as well to minimize the size.

The command accepts the result filename as argument. If omitted, it defaults to
`clean.sql`.


== Installation ==

= Install as Git submodule =

1. Add the package as submodule.
    ```sh
    git submodule add --name wp-cli-db-export-clean git@github.com:makers99/wp-cli-db-export-clean.git .wp-cli/packages/db-export-clean
    ```

2. Register the command for early WP-CLI bootstrap.
    ```sh
    echo -e "require:\n  - .wp-cli/packages/db-export-clean/package.php" >> wp-cli.yml
    ```
    Or manually:
    ```sh
    vi wp-cli.yml
    ```
    ```yaml
    require:
      - .wp-cli/packages/db-export-clean/plugin.php
    ```

= Install with Composer =

1. Install the package with Composer.
    ```sh
    composer config repositories.wp-cli-db-export-clean git https://github.com/makers99/wp-cli-db-export-clean.git
    composer require makers99/wp-cli-db-export-clean:dev-master
    ```
    Note: Do not use `--dev` to install as `require-dev`, because export-clean
    is typically used in production.

2. Register the command for early WP-CLI bootstrap.
    ```sh
    echo -e "require:\n  - vendor/makers99/wp-cli-db-export-clean/package.php" >> wp-cli.yml
    ```
    Or manually:
    ```sh
    vi wp-cli.yml
    ```
    ```yaml
    require:
      - vendor/makers99/wp-cli-db-export-clean/package.php
    ```


= Requirements =

* PHP 7.4 or later.


= Adding patches =

```console
$ cd wp-content/plugins/example-plugin
$ git diff --relative . > ../../../.wp-cli/packages/shared-patches/patches/example-plugin.0001.fix.relevant-context-keywords.patch
```
