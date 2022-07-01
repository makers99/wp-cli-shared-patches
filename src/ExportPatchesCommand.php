<?php

namespace Makers99\SharedPatches;

use WP_CLI;

/**
 * WP-CLI command for shared patches.
 *
 * Exports a json file mapping all patches, to be used with composer-patches on
 * Flynt based projects.
 */
class ExportPatchesCommand extends \WP_CLI_Command {

  /**
   * Invoke to regenerate patches.json.
   *
   * @synopsis
   */
  public function __invoke(): void {
    // Return if composer.json is not was at root.
    $composer_path = dirname(ABSPATH) . '/../composer.json';
    if (!file_exists($composer_path)) {
      WP_CLI::error('No root composer.json was found (path was: ' . $composer_path . ').');
    }
    // Read project root composer.json, to only include relevant patches.
    $composer_json = json_decode(file_get_contents($composer_path), TRUE);
    if (!$composer_json) {
      return;
    }

    $dependencies = array_map(
      fn ($dependency) => basename($dependency),
      array_keys($composer_json['require'])
    );

    $patches = [];
    foreach (glob(dirname(__DIR__) . "/patches/*.patch") as $patch) {
      $patch_parts = explode('.', basename($patch));
      $plugin = reset($patch_parts);
      $plugin_name = (array) array_filter($dependencies, fn($dependency) => $dependency === $plugin);
      $plugin_name = reset($plugin_name);
      if (!$plugin_name) {
        continue;
      }
      end($patch_parts);
      $patches[$plugin_name][prev($patch_parts)] = './.wp-cli/packages/shared-patches/patches/' . basename($patch);
    }
    // Map all references to 'patches.json', and then reference this as
    // `patches-file` in the root composer.json.
    if (file_put_contents(dirname(ABSPATH) . '/../patches.json', json_encode(['patches' => $patches], JSON_PRETTY_PRINT))) {
      WP_CLI::success('Patches successfully mapped at ' . dirname(ABSPATH) . '/patches.json');
    };
  }

}
