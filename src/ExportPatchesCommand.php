<?php

namespace Makers99\SharedPatches;

use DirectoryIterator;
use WP_CLI;

/**
 * WP-CLI command for shared patches.
 */
class ExportPatchesCommand extends \WP_CLI_Command {

  /**
   * Exports a json file mapping all patches.
   *
   * This can then be used with composer-patches on all of our Flynt based
   * projects.
   *
   * @synopsis
   */
  public function __invoke(): void {
    // Return if this is not Flynt based.
    $composer_path = dirname(ABSPATH) . '/../composer.json';
    if (!file_exists($composer_path)) {
      return;
    }
    $composer_json = json_decode(file_get_contents($composer_path), TRUE);
    // foreach ($composer_json['require'] as $dependency => $version) {
    //   $dependencies[] = $dependency;
    // }
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
    file_put_contents(dirname(__DIR__) . '/patches/patches.json', json_encode(['patches' => $patches]));
  }

}
