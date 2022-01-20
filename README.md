=== wp-cli-plugin-patch ===
Contributors: makers99
Tags: wp-cli, plugin, patch, fix
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Adds the WP-CLI command `wp plugin patch` to apply patches to one or more
plugins.

Allows plugins to be updated to later versions while retaining bugfixes that
have not been included into the upstream versions by the maintainers yet.

The command follows the synopsis of `wp plugin patch`. It accepts one or more
plugin names for which to apply patches, or the option `--all`.

```console
$ wp plugin patch example-plugin other-plugin
$ wp plugin patch --all
```

The patches are shared as part of this package, see folder `./patches/`.

All patches must be "patch serials" in the format of `git format-patch`. They will be applied using `git am`. More details on this in the _Creating patches_ chapter.

```console
$ wp patch create example-plugin c03314 fix keywords-context-info
```


== Installation ==

= Install as Git submodule =

1. Add the package as submodule.
    ```sh
    git submodule add --name wp-cli-shared-patches git@github.com:makers99/wp-cli-shared-patches.git .wp-cli/packages/shared-patches
    ```

2. Register the command for early WP-CLI bootstrap.
    ```sh
    echo -e "require:\n  - .wp-cli/packages/shared-patches/package.php" >> wp-cli.yml
    ```
    Or manually:
    ```sh
    vi wp-cli.yml
    ```
    ```yaml
    require:
      - .wp-cli/packages/shared-patches/plugin.php
    ```

= Install with Composer =

1. Install the package with Composer.
    ```sh
    composer config repositories.wp-cli-shared-patches git https://github.com/makers99/wp-cli-shared-patches.git
    composer require --dev makers99/wp-cli-shared-patches:dev-master
    ```

2. Register the command for early WP-CLI bootstrap.
    ```sh
    echo -e "require:\n  - vendor/makers99/wp-cli-shared-patches/package.php" >> wp-cli.yml
    ```
    Or manually:
    ```sh
    vi wp-cli.yml
    ```
    ```yaml
    require:
      - vendor/makers99/wp-cli-shared-patches/package.php
    ```


= Requirements =

* PHP 7.4 or later.


= Creating patches =

All patches are created from an existing commit, so that the author, committer,
date, and further context is included in the patch file.

Each patch filename must be in the following structure, delimited by dots:

1. Name of the plugin (folder).
2. Patch number, starting from `0000`.
3. Type of change, either `"fix"` or `"feature"`.
4. Keywords to provide approximate context, delimited by hyphens.


```console
$ export PATCHES_DIR=.wp-cli/packages/shared-patches/patches
$ export COMMIT=abcdefgh
$ cd wp-content/plugins/example-plugin
$ git format-patch --relative --stdout $COMMIT~1..$COMMIT > \
  $PATCHES_DIR/example-plugin.0001.fix.relevant-context-keywords.patch
```
(Variables `PATCHES_DIR` and `COMMIT` used for clarity only.)

Notes

- `git format-patch` does not include merge commits. You can create a patch compatible with `git am` following the instructions in https://stackoverflow.com/a/8840381/811306.
