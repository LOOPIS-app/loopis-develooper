<?php
/**
 * Function to handle activation/deactivation button states.
 * 
 * @package LOOPIS_Develooper
 * @subpackage Devtools
 */

/**
 * Handle button activation status and summary display for plugins
 * 
 * @param bool $is_all_worked_1 // for example, is_all_installed or is_all_activated
 * @param bool $is_all_worked_2 // for example, is_all_uninstalled or is_all_deactivated
 * @param array $lists // plugin lists
 * @param string $key1 // either 'install' or 'activate'
 * @return array Updated status of all worked variables
 */
function button_activation_handler($is_all_worked_1, $is_all_worked_2, $lists, $key1)
{
    loopis_elog_function_start('button_activation_handler');

    // Validate key1 parameter
    if ($key1 != 'install' && $key1 != 'activate') {
        loopis_elog_first_level(" Invalid key1 parameter: " . $key1 . ". Must be either 'install' or 'activate' ");
        return [$is_all_worked_1, $is_all_worked_2];
    }

    $all_active = $is_all_worked_1;
    $all_inactive = $is_all_worked_2;

    // Get count of installed plugins
    $count = list_counter($lists, $key1);

    // Get max count of plugins
    $max_plugin_list_count = '';

    if ($key1 == 'install') {
        $max_plugin_list_count = count($lists);
    } else {
        $max_plugin_list_count = list_counter($lists, 'install');
    }


    // Determine button states based on counts
    // if count equals max, all are active(all installed/activated)
    if ($count == $max_plugin_list_count) {
        $all_active = true;
        $all_inactive = false;

    } // if count is lower than max but greater than 0, will both be false due to partial activation(some installed/activated)
    else if ($count > 0 && $count < $max_plugin_list_count) {
        $all_active = false;
        $all_inactive = false;

    } // if count is 0, all plugins are inactive(none installed/activated)
    else {
        $all_active = false;
        $all_inactive = true;
    }

    loopis_elog_function_end_success('button_activation_handler');
    return [$all_active, $all_inactive];
}

/**
 * Summary of counter. Counts amount of installed/activated plugins
 * @param array $lists
 * @return int
 */
function list_counter($lists, $keywords)
{

    loopis_elog_function_start('list_counter');
    // Validate keywords parameter
    if ($keywords != 'install' && $keywords != 'activate') {
        loopis_elog_first_level(" Invalid keywords parameter: " . $keywords . ". Must be either 'install' or 'activate' ");
        return 0;
    }


    // Initialize counter
    $counter = 0;

    // Loop through each plugin in the list
    foreach ($lists as $plugin) {
        // if keywords is install, check if plugin directory exists
        if ($keywords == 'install') {
            $plugin_path = WP_PLUGIN_DIR . '/' . $plugin['slug'];
            if (file_exists($plugin_path)) {
                $counter++;
            }
        } // if keywords is activate, check if plugin is active
        else if ($keywords == 'activate') {
            $plugin_main = $plugin['main'];
            if (is_plugin_active($plugin_main)) {
                $counter++;
            }
        }
    }

    loopis_elog_function_end_success('list_counter');
    return $counter;
}
