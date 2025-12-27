<?php
/**
 * Function to delete develooper-installed plugins.
 *
 * This file is included from the WP admin page with the same name. (When implemented. Copy from LOOPIS Config below.)
 * 
 * @package LOOPIS_Develooper
 * @subpackage Devtools
 */


if (!defined('ABSPATH')) { 
    exit; 
}

require_once LOOPIS_DEVELOOPER_DIR . 'assets/plugins/plugin_list.php';

/**
 * Delete all develooper-installed plugins from wp-content/plugins/
 * 
 * @return void
 */
function develooper_plugins_delete() {
    
    loopis_elog_function_start('develooper_plugins_delete');

    // Plugin main file in /wp-content/plugins
    $plugins = plugin_list();


    // For each item in list deactivate and delete
    foreach ($plugins as $plugin) {

        $plugin_slug = $plugin['slug'];
        $plugin_main = $plugin['main'];
        // Deactivate if active
        if (is_plugin_active($plugin_main)) {
            deactivate_plugins($plugin_main);
        }

        // Delete plugin if exists
        $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_slug; 
        if (file_exists($plugin_path)) {
            // Delete plugin
            $result = @delete_plugins([$plugin_main]);
            // Handle Error
            if (is_wp_error($result)) {
                loopis_elog_first_level(" Failed to uninstall $plugin_slug: " . $result->get_error_message());
            } else {
                loopis_elog_first_level(" Successfully uninstalled $plugin_slug");
            }
        } else {
            loopis_elog_first_level(" File " . $plugin_path .  " does not exist ");
        }
    }
    loopis_elog_function_end_success('develooper_plugins_delete');
}

function develooper_plugin_delete($slug, $main) {
    
    loopis_elog_function_start('develooper_plugin_delete');

    // Deactivate if active
    if (is_plugin_active($main)) {
        deactivate_plugins($main);
    }

    // Delete plugin if exists
    $plugin_path = WP_PLUGIN_DIR . '/' . $slug; 
    if (file_exists($plugin_path)) {
        loopis_elog_first_level(" File exist ");
        // Delete plugin
        $result = @delete_plugins([$main]);
        // Handle Error
        if (is_wp_error($result)) {
            loopis_elog_first_level(" Failed to uninstall $slug: " . $result->get_error_message());
        } else {
            loopis_elog_first_level(" Successfully uninstalled $slug");
        }
    } else {
        loopis_elog_first_level(" File " . $plugin_path .  " does not exist ");
    }
    
    loopis_elog_function_end_success('develooper_plugin_delete');
}
