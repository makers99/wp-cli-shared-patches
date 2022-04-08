<?php

namespace Makers99\SharedPatches;

use DirectoryIterator;
use WP_CLI;
use WP_CLI\Utils;

/**
 * WP-CLI command for shared patches.
 */
class PluginCommand extends \WP_CLI_Command {

  /**
   * Prefix for hook names.
   *
   * @var string
   */
  const PREFIX = 'shared-patches';

  /**
   * Plugins directory path.
   *
   * @var string
   */
  protected $plugins_path;

  /**
   * Applies patches for one or more plugins.
   *
   * @subcommand patch
   * @synopsis [<plugin>...] [--all] [--plugins-path]
   */
  public function __invoke(array $args, array $options): void {
    if (empty($args) && !isset($options['all'])) {
      WP_CLI::error('Please specify one or more plugins, or use --all.');
    }
    self::$plugins_path = $options['plugins-path'] ?? WP_PLUGIN_DIR;
    $plugins = [];
    if (!empty($options['all'])) {
      // Retrieve all plugins with their folders relative from the project root.
      // get_plugins() retrieves additional metadata that is not necessary here.
      foreach (new DirectoryIterator(self::$plugins_path) as $fileinfo) {
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
        if (file_exists($plugin_path = self::$plugins_path . '/' . $plugin_name)) {
          $plugins[$plugin_name] = str_replace(ABSPATH, '', $plugin_path);
        }
        else {
          WP_CLI::error(sprintf("Plugin not found: '%s'", $plugin_name));
        }
      }
    }
    if (empty($plugins)) {
      WP_CLI::error(sprintf('No plugins found in %s', self::$plugins_path));
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
    // --keep-cr retains Windows line endings in the patch, if any.
    $command = 'git am --directory %s --3way --keep-cr %s';
    $command = Utils\esc_cmd($command, $plugin_folder, $patch_filepath);
    $processrun = WP_CLI::launch($command, FALSE, TRUE);
    if ($processrun->return_code !== 0) {
      // @see WP_CLI\ProcessRun::__toString()
      WP_CLI::error((string) $processrun, false);
      WP_CLI::launch('git am --abort', FALSE, TRUE);
    }
  }

}
