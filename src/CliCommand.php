<?php

namespace Makers99\SharedPatches;

use DirectoryIterator;
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
   * Applies patches for one or more plugins.
   *
   * @subcommand patch
   * @synopsis [<plugin>...] [--all]
   */
  public function __invoke(array $args, array $options): void {
    if (empty($args) && !isset($options['all'])) {
      WP_CLI::error('Please specify one or more plugins, or use --all.');
    }
    $plugins = [];
    if (!empty($options['all'])) {
      // Retrieve all plugins with their folders relative from the project root.
      // get_plugins() retrieves additional metadata that is not necessary here.
      foreach (new DirectoryIterator(WP_PLUGIN_DIR) as $fileinfo) {
        if ($fileinfo->isDir() && !$fileinfo->isDot()) {
          // Single file plugins (like the default hello.php) are not supported.
          $plugins[$fileinfo->getFilename()] = str_replace(ABSPATH, '', $fileinfo->getPathname());
        }
      }
    }
    else {
      // Retrieve the folders for each passed plugin, relative from the project
      // root.
      foreach ($args as $plugin_name) {
        if (file_exists($plugin_path = WP_PLUGIN_DIR . '/' . $plugin_name)) {
          $plugins[$plugin_name] = str_replace(ABSPATH, '', $plugin_path);
        }
        else {
          WP_CLI::error(sprintf("Plugin not found: '%s'", $plugin_name));
        }
      }
    }
    if (empty($plugins)) {
      WP_CLI::error(sprintf('No plugins found in %s', WP_PLUGIN_DIR));
    }

    foreach ($plugins as $plugin_name => $plugin_folder) {
      $patches = static::getPatches($plugin_name);
      if ($patches) {
        WP_CLI::line(sprintf("Applying patches for plugin '%s':", $plugin_name));
        WP_CLI::line(implode("\n", array_keys($patches)));
        foreach ($patches as $patch_filepath) {
          static::applyPatch($plugin_name, $plugin_folder, $patch_filepath);
        }
      }
    }
  }

  /**
   * Returns available patches for a given plugin name.
   *
   * @param string $plugin_name
   *   The name of the plugin.
   *
   * @return array
   *   A list of available patches for the plugin.
   */
  public static function getPatches(string $plugin_name): array {
    $filepaths = [];
    foreach (glob(dirname(__DIR__) . "/patches/{$plugin_name}.*.patch") as $filepath) {
      $filepaths[basename($filepath)] = $filepath;
    }
    return $filepaths;
  }

  /**
   * Applies a patch to a plugin.
   *
   * @param string $plugin_name
   *   The name of the plugin to patch.
   * @param string $plugin_folder
   *   The folder of the plugin, relative to the Git project root.
   * @param string $patch_filepath
   *   The filepath of the patch to apply.
   */
  public static function applyPatch(string $plugin_name, string $plugin_folder, string $patch_filepath): void {
    $command = 'git apply --directory %s --3way --intent-to-add %s';
    $command = Utils\esc_cmd($command, $plugin_folder, $patch_filepath);
    $processrun = WP_CLI::launch($command, FALSE, TRUE);
    if ($processrun->return_code !== 0) {
      // @see WP_CLI\ProcessRun::__toString()
      WP_CLI::error((string) $processrun, false);
    }
  }

  /**
   * @implements upgrader_post_install
   * @when after_wp_load
   */
  public static function post_install($response, $hook_extra, $result) {
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
    $plugin_name = $result['destination_name'];
    // Sanitize the input for glob() filesystem operation.
    $plugin_name = preg_replace('@[^a-zA-Z0-9_-]@', '', $plugin_name);

    $patches = static::getPatches($plugin_name);
    foreach ($patches as $patch_filepath) {
      static::applyPatch($plugin_name, $extension_folder, $patch_filepath);
    }

    return $response;
  }

}
