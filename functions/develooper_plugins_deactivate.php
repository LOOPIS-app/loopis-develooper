<?php
/**
 * Function to deactivate develooper-installed plugins for both all plugins and individual plugin.
 *
 * This file is included from the WP admin page with the same name. (When implemented. Copy from LOOPIS Config below.)
 * 
 * @package LOOPIS_Develooper
 * @subpackage Devtools
 */


if (!defined('ABSPATH')) {
    exit;
}

// import plungin list
// require_once LOOPIS_DEVELOOPER_DIR . 'assets/plugins/plugin_list.php';

/**
 * Deactivate all plugins. Used when "Deactivate all plugins" button is pressed.
 * 
 * @return void
 */
function develooper_plugin_deactivate_all()
{
    loopis_elog_function_start('develooper_plugins_deactivate_all');

    // import plungin list
    require_once LOOPIS_DEVELOOPER_DIR . 'assets/plugins/plugin_list.php';

    // Fetch plugin list (assets/plugins/plugin_list.php)
    $plugins = plugin_list();

    foreach ($plugins as $plugin) {
        $plugin_slug = $plugin['slug'];
        $plugin_main = $plugin['main'];

        // Full path to plugin
        $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_slug;

        // Check if plugin files exist
        if (!file_exists($plugin_path)) {
            loopis_elog_first_level(" File " . $plugin_path . " does not exist ");
            continue;
        }

        // Deactivate if active
        if (is_plugin_active($plugin_main)) {
            deactivate_plugins($plugin_main);
        }
    }
    loopis_elog_function_end_success('develooper_plugins_deactivate_all');
}

/**
 * Deactivate an individual plugin based on its slug and main file.
 * 
 * @return void
 */

function develooper_plugin_deactivate($slug, $main)
{
    loopis_elog_function_start('develooper_plugin_deactivate');

    // Full path to plugin
    $plugin_path = WP_PLUGIN_DIR . '/' . $slug;

    // Check if plugin files exist
    if (!file_exists($plugin_path)) {
        loopis_elog_first_level(" File " . $plugin_path . " does not exist ");
        return;
    }

    // Deactivate if active
    if (is_plugin_active($main)) {
        deactivate_plugins($main);
    }

    loopis_elog_function_end_success('develooper_plugin_deactivate');
}