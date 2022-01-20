<?php

// phpcs:disable
/*
  Plugin Name: WP-CLI Shared Patches
  Version: 1.0.0
  Text Domain: shared-patches
  Description: Applies patches to WordPress plugins.
  Author: makers99
  Author URI: https://makers99.com
  License: GPL-2.0+
  License URI: https://www.gnu.org/licenses/gpl-2.0
*/
// phpcs:enable

namespace Makers99\SharedPatches;

use WP_CLI;

/**
 * Loads PSR-4-style plugin classes.
 */
function classloader($class) {
  static $ns_offset;
  if (strpos($class, __NAMESPACE__ . '\\') === 0) {
    if ($ns_offset === NULL) {
      $ns_offset = strlen(__NAMESPACE__) + 1;
    }
    include __DIR__ . '/src/' . strtr(substr($class, $ns_offset), '\\', '/') . '.php';
  }
}
spl_autoload_register(__NAMESPACE__ . '\classloader');


if (is_callable('WP_CLI::add_command')) {
  WP_CLI::add_command('plugin patch', __NAMESPACE__ . '\CliCommand');
  WP_CLI::add_hook('after_wp_load', function () {
    add_filter('upgrader_post_install', __NAMESPACE__ . '\CliCommand::post_install', 10, 3);
  });
}
elseif (is_callable('add_filter')) {
  add_filter('upgrader_post_install', __NAMESPACE__ . '\CliCommand::post_install', 10, 3);
}
