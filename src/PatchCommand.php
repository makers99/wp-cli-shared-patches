<?php

namespace Makers99\SharedPatches;

use WP_CLI;
use WP_CLI\Utils;

/**
 * WP-CLI command for shared patches.
 */
class PatchCommand extends \WP_CLI_Command {

  /**
   * Creates a patch file for a given commit.
   *
   * @synopsis <plugin> <commit> <type> <keywords>
   */
  public function create(array $args, array $options): void {
    [$plugin_name, $commit, $type, $keywords] = $args;

    $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_name;
    if (!file_exists($plugin_path)) {
      WP_CLI::error(sprintf("Plugin '%s' not found in %s.", $plugin_name, $plugin_path));
    }

    $patches_path = dirname(__DIR__) . '/patches';
    if (!file_exists($patches_path)) {
      WP_CLI::error(sprintf("Patch files directory not found in '%s'.", $patches_path));
    }

    $patch_filename = implode('.', [
      $plugin_name,
      str_pad(count(PluginCommand::getPatches($plugin_name)) + 1, 4, '0', STR_PAD_LEFT),
      $type,
      $keywords,
      'patch',
    ]);

    $command = 'cd %s; git format-patch --relative --stdout %s~1..%s > %s/%s';
    $command = Utils\esc_cmd($command, $plugin_path, $commit, $commit, $patches_path, $patch_filename);
    $processrun = WP_CLI::launch($command, FALSE, TRUE);
    if ($processrun->return_code !== 0) {
      // @see WP_CLI\ProcessRun::__toString()
      WP_CLI::error((string) $processrun, false);
    }
  }


  /**
   * Exports a json file mapping all patches.
   *
   * This can then be used with composer-patches on all of our Flynt based
   * projects.
   *
   * @synopsis
   */
  public static function exportJson(): void {
    WP_CLI::success("Hi");
    // $filepaths = [];
    // foreach (glob(dirname(__DIR__) . "/patches/{$plugin_name}.*.patch") as $filepath) {
    //   $filepaths[basename($filepath)] = $filepath;
    // }
  }

}
