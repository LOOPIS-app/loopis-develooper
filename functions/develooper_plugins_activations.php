<?php
/**
 * Function to activate and deactivate develooper-installed plugins.
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
require_once LOOPIS_DEVELOOPER_DIR . 'assets/plugins/plugin_list.php';

/**
 * Activate all plugins. Used when "Activate all plugins" button is pressed.
 * 
 * @return void
 */
function develooper_plugin_activate_all()
{
    loopis_elog_function_start('develooper_plugins_activate_all');

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

        // Activate if not active
        if (!is_plugin_active($plugin_main)) {
            activate_plugins($plugin_main);
        }
    }
    loopis_elog_function_end_success('develooper_plugins_activate_all');
}

/**
 * Deactivate all plugins. Used when "Deactivate all plugins" button is pressed.
 * 
 * @return void
 */
function develooper_plugin_deactivate_all()
{
    loopis_elog_function_start('develooper_plugins_deactivate_all');

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
