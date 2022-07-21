<?php

namespace Makers99\SharedPatches;

/**
 * WP-CLI command for shared patches.
 *
 * Exports a json file mapping all patches, to be used with composer-patches on
 * Bedrock based projects.
 */
class ExportPatchesCommand extends \WP_CLI_Command {

  /**
   * Invoke to regenerate patches.json.
   *
   * @synopsis
   */
  public function __invoke(): void {
    // Return if this is not Bedrock based.
    $composer_path = dirname(ABSPATH) . '/../composer.json';
    if (!file_exists($composer_path)) {
      \WP_CLI::error('No root composer.json was found (path was: ' . $composer_path . ').');
    }
    // Read project root composer.json, to only include relevant patches.
    $composer_json = json_decode(file_get_contents($composer_path), TRUE);
    if (!$composer_json) {
      return;
    }

    $dependencies = [];
    foreach(array_keys($composer_json['require']) as $dependecy) {
      $dependencies = $dependencies + [basename($dependecy) => $dependecy];
    }

    if (!$dependencies) {
      \WP_CLI::error('No dependencies found in composer.json.');
    }

    $patches = [];
    foreach (glob(dirname(__DIR__) . "/patches/*.patch") as $patch) {
      $patch_parts = explode('.', basename($patch));
      $plugin_name = $patch_parts[0];
      $keywords = $patch_parts[3];
      if (!isset($dependencies[$plugin_name])) {
        continue;
      }
      $patches[$dependencies[$plugin_name]][$keywords] = './.wp-cli/packages/shared-patches/patches/' . basename($patch);
    }
    // Map all references to 'patches.json', and then reference this as
    // `patches-file` in the root composer.json.
    $patchesfile = dirname(ABSPATH) . '/../patches.json';
    if (file_put_contents($patchesfile, json_encode(['patches' => $patches], JSON_PRETTY_PRINT))) {
      \WP_CLI::success('Patches successfully mapped at ' . realpath($patchesfile));
    };
  }

}
