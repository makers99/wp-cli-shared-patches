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
    // Return if this is not Flynt based.
    $composer_path = dirname(ABSPATH) . '/../composer.json';
    if (!file_exists($composer_path)) {
      WP_CLI::error('No root composer.json was found (path was: ' . $composer_path . ').');
    }
    // Read project root composer.json, to only include relevant patches.
    $composer_json = json_decode(file_get_contents($composer_path), TRUE);
    $dependencies = array_keys($composer_json['require']);
    $patches = [];
    foreach (glob(dirname(__DIR__) . "/patches/*.patch") as $patch) {
      $patch_parts = explode('.', basename($patch));
      $plugin = reset($patch_parts);
      $plugin_name = reset(array_filter($dependencies, fn($dependency) => strpos($dependency, $plugin) !== FALSE));
      if (!$plugin_name) {
        continue;
      }
      end($patch_parts);
      $patches[$plugin_name][prev($patch_parts)] = $patch;
    }
    // Map all references to 'patches.json', and then reference this as
    // `patches-file` in the root composer.json.
    // The file is not tracked with Git since it would differ from project to
    // project.
    file_put_contents(dirname(__DIR__) . '/patches/patches.json', json_encode(['patches' => $patches]));
  }

}
