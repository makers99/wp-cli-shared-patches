=== wp-cli-plugin-patch ===
Contributors: makers99, netzstrategen
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
plugin names for which to apply patches, or the option `--all`:

```console
$ wp plugin patch example-plugin other-plugin
$ wp plugin patch --all
```

The patches are shared as part of this package, see folder `./patches/`.

There is also command to create a new patch:
```console
$ wp patch create example-plugin c03314 fix keywords-context-info
```

All patches must be "patch serials" in the format of `git format-patch`. They
will be applied using `git am`. More details on this in the _Creating patches_
chapter.


== Installation ==

= Install as Git submodule =

1. In the root folder of your project, add the package as submodule.
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
      - .wp-cli/packages/shared-patches/package.php
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

Only the changes of the specified commit are included in the patch file. Squash
your changes into a single commit if you have multiple commits.

Each patch filename must be in the following structure, delimited by dots:

1. Name of the plugin (folder).
2. Patch number, starting from `0000`. Automatically generated.
3. Type of change, either `"fix"` or `"feature"`.
4. Keywords to provide approximate context, delimited by hyphens.

```console
$ wp patch create example-plugin c03314 fix keywords-context-info
```

This will create a new patch file in the [`/patches`](patches) folder, which you
can then add to the repository.

Here is how the comand works under the hood:

```console
$ export PATCHES_DIR=.wp-cli/packages/shared-patches/patches
$ export COMMIT=abcdefgh
$ cd wp-content/plugins/example-plugin
$ git format-patch --relative --stdout $COMMIT~1..$COMMIT > \
  $PATCHES_DIR/example-plugin.0001.fix.relevant-context-keywords.patch
```
(Variables `PATCHES_DIR` and `COMMIT` used for clarity only.)

Notes

- On GitHub, you can append `".patch"` to the end of the URL of a commit (or
  PR) to get the changes in the patch serial format. ([example](https://github.com/netzstrategen/wordpress-core-standards/commit/dc95a2eac5d565675c1c1c5fb008c9ebbc8ed8e0.patch))

  - If the commit is from a website project repository and not a plugin
    repository, you need to adjust the file paths in the .patch file to be
    relative to the plugin folder.

  - Ensure to download the original raw file content and do not copy/paste what
    you see in the browser page, because the HTML/web formatting in the browser
    does not include crucial whitespace characters. You can use the _Sources_
    tab in the browser console to download the original raw response.

- `git format-patch` does not include merge commits. You can create a patch
  compatible with `git am` following the instructions in
  https://stackoverflow.com/a/8840381/811306.

Documenting upstream

If there is a public issue or PR in the upstream vendor repository or issue
tracker, then ensure to add it to the commit description of the patch:

```
Upstream: https://github.com/makers99/wp-cli-shared-patches/pull/10
```
(The URL should point to the upstream vendor issue, of course.)

Ideally add this info in the initial/original commit message already.


= Resolving patch conflicts =

Repeat the failing command that you see in the error message; e.g.:

```console
$ git am --directory 'wp-content/plugins/woocommerce-german-market' --3way --keep-cr '.wp-cli/packages/shared-patches/patches/woocommerce-german-market.0001.feature.shop-standards-delivery-times.patch'
```

This results in a `git am` merge conflict, which you can resolve like any other
merge or rebase conflict.

```console
$ git am --show-current-patch
$ vi wp-content/plugins/woocommerce-german-market/inc/WGM_Template.php
# Resolve conflict markers as with a regular merge

$ git status
$ git add wp-content/plugins/woocommerce-german-market/inc/WGM_Template.php
$ git status

$ git am --continue

$ wp patch create woocommerce-german-market d8e0e9b67 feature shop-standards-delivery-times
$ cd .wp-cli/packages/shared-patches/patches/
$ rm woocommerce-german-market.0001.feature.shop-standards-delivery-times.patch
$ mv woocommerce-german-market.0005.feature.shop-standards-delivery-times.patch woocommerce-german-market.0001.feature.shop-standards-delivery-times.patch
$ git status
$ git add woocommerce-german-market.0001.feature.shop-standards-delivery-times.patch
$ git commit -m "Updated woocommerce-german-market shop-standards delivery times patch."
$ git push
$ cd -
```

However, if you see conflict markers but no actual changes (in all files affected by the patch), then the patch was incorporated into the new upstream release.

```console
$ git am --show-current-patch
$ vi wp-content/plugins/woocommerce-german-market/inc/WGM_Installation.php
# Search for conflict markers:
/<<<<<

$ vi wp-content/plugins/woocommerce-german-market/WooCommerce-German-Market.php
# Search for conflict markers:
/<<<<<

$ git status
$ git am --abort

$ cd .wp-cli/packages/shared-patches/patches
$ git rm woocommerce-german-market.0004.fix.performance-admin_url-string-translations.patch
$ git commit -m "Removed patch woocommerce-german-market.0004.fix.performance-admin_url-string-translations."
$ cd -
```
