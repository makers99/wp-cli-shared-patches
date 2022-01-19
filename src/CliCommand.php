<?php

namespace Makers99\SharedPatches;

use WP_CLI;
use WP_CLI\Utils;

/**
 * WP-CLI command for shared patches.
 */
class CliCommand extends \WP_CLI_Command {

  /**
   * Prefix for hook names.
   *
   * @var string
   */
  const PREFIX = 'shared-patches';

  /**
   * @implements upgrader_post_install
   * @when after_wp_load
   */
  public static function post_install($response, $hook_extra, $result) {
    // var_dump(func_get_args());
    if ($response !== TRUE) {
      return $response;
    }
    // Sample parameters:
    // - type: plugin
    // - action: install
    if ($hook_extra['type'] !== 'plugin') {
      return $response;
    }
    // Sample parameters:
    // - destination: /htdocs/wp-content/plugins/gallerya/
    // - destination_name: gallerya
    // - local_destination: /htdocs/wp-content/plugins
    // - remote_destination: /htdocs/wp-content/plugins/gallerya/
    // - clear_destination: TRUE
    $extension_folder = $result['destination'];
    $extension_folder = str_replace(ABSPATH, '', $extension_folder);
    $name = $result['destination_name'];
    // Sanitize the input for glob() filesystem operation.
    $name = preg_replace('@[^a-zA-Z0-9_-]@', '', $name);

    $patches = static::getPatches($name);
    foreach ($patches as $patch_filepath) {
      $command = 'git apply --directory %s --3way --intent-to-add %s';
      $command = Utils\esc_cmd($command, $extension_folder, $patch_filepath);
      $processrun = WP_CLI::launch($command, FALSE, TRUE);
      if ($processrun->return_code !== 0) {
        // @see WP_CLI\ProcessRun::__toString()
        WP_CLI::error((string) $processrun);
      }
    }

    return $response;
  }

  public static function getPatches($name) {
    $filepaths = [];
    foreach (glob(dirname(__DIR__) . "/patches/{$name}.*.patch") as $filepath) {
      $filepaths[] = $filepath;
    }
    return $filepaths;
  }

  /**
   * Applies a patch file for a given plugin.
   *
   * @subcommand patch
   * @synopsis <plugin>
   */
  public function __invoke(array $args, array $options) {
  }

}
