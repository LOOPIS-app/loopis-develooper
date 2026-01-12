<?php
/**
 * WP Admin page for configuring developer plugins.
 * 
 * @package LOOPIS_Develooper
 * @subpackage Admin-page
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

require_once LOOPIS_DEVELOOPER_DIR . 'functions/develooper_plugins_output.php';
require_once LOOPIS_DEVELOOPER_DIR . 'functions/develooper_roles_output.php';
require_once LOOPIS_DEVELOOPER_DIR . 'functions/develooper_plugins_activations.php';
require_once LOOPIS_DEVELOOPER_DIR . 'assets/scripts/loader.php';

setup();
// Function to render the page
function develooper_plugins_page()
{
    ?>
    <div class="wrap">
        <!-- Page title and description-->
        <h1>🧩 Develooper plugins <span class="h1-right">Version <?php echo esc_html(LOOPIS_DEVELOOPER_VERSION); ?></span>
        </h1>
        <p class="description">💡 Useful plugins for develoopers.</p>
        <?php
        // Show success messages
        if (isset($_GET['action'])) {
            if ($_GET['action'] === 'installed') {
                echo '<div class="notice notice-success is-dismissible"><p>✅ Plugins have been successfully installed!</p></div>';
            } else if ($_GET['action'] === 'deleted') {
                echo '<div class="notice notice-success is-dismissible"><p>❌ Plugins have been successfully uninstalled!</p></div>';
            } else if ($_GET['action'] === 'activated') {
                echo '<div class="notice notice-success is-dismissible"><p>✅ Plugins have been successfully activated!</p></div>';
            } else if ($_GET['action'] === 'deactivated') {
                echo '<div class="notice notice-success is-dismissible"><p>❌ Plugins have been successfully deactivated!</p></div>';
            }
        }
        ?>
        <!-- Page content-->
        <?php
        echo '<form id="plugin-form" method="POST">';
        $is_all_installed = false;
        $is_all_uninstalled = true;

        require_once LOOPIS_DEVELOOPER_DIR . 'assets/plugins/plugin_list.php';

        //fetch plugin lists
        $plugins = plugin_list();

        // Determine button states
        list($is_all_installed, $is_all_uninstalled) = button_activation_handler($is_all_installed, $is_all_uninstalled, $plugins, 'install', 'uninstall');

        // Button display logic
        // If all installed, disable install button, opposite for uninstall. 
        $disable_install = $is_all_installed ? 'disabled ' : '';
        $disable_uninstall = $is_all_uninstalled ? 'disabled ' : '';

        // Logic for activate/deactivate buttons
        $disable_activate = '';
        $disable_deactivate = '';

        // disable both activate and deactivate if all plugins aren't installed
        if ($is_all_uninstalled) {
            $disable_activate = 'disabled';
            $disable_deactivate = 'disabled';
            // if some of plugins are installed
        } else {
            //number or installed plugins
            $install_counter = list_counter($plugins, 'install');
            //number of activated plugins
            $activate_counter = list_counter($plugins, 'activate');

            //if all installed plugins are deactivated, disable Deactivate all plugins button
            if ($activate_counter == 0) {
                $disable_deactivate = 'disabled';
                // if all installed plugins are activated, disable Activate all plugins button
            } else if ($install_counter == $activate_counter) {
                $disable_activate = 'disabled';
            }
        }


        // Render plugin buttons for all plugins
        echo '<button ' . $disable_install . ' class="button button-primary bunch-action-btn loading-btn wp-blue" type="submit" name="develooper_plugins_install">Install all plugins</button>';
        echo '<button ' . $disable_uninstall . ' class="button button-primary bunch-action-btn loading-btn wp-red" type="submit" name="develooper_plugins_delete">Uninstall all plugins</button>';
        echo '<button ' . $disable_activate . ' class="button button-primary bunch-action-btn loading-btn wp-green" type="submit" name="develooper_plugins_activate_all">Activate all plugins</button>';
        echo '<button ' . $disable_deactivate . ' class="button button-primary bunch-action-btn loading-btn wp-orange" type="submit" name="develooper_plugins_deactivate_all">Deactivate all plugins</button>';
        // loading display when buttons are clicked
        echo '<div class ="loader" id="loader"></div>';
        echo '</form>';


        render_user_roles_styles();
        // Render the plugins table style
        plugin_table_style();

        // Render the plugins table with plugins
        render_loopis_plugins_table();
        // loader display activations
        activate_loader('.loading-btn', 'loader', 'plugin-form');
        ?>
    </div>
    <?php
}

/**
 * Setup function for handling plugin actions
 * Actived when one of buttons for all plugins is clicked
 * @return void
 */
function setup()
{
    if (isset($_POST['develooper_plugins_install'])) {
        require_once LOOPIS_DEVELOOPER_DIR . 'functions/develooper_plugins_install.php';
        develooper_plugins_install();

        wp_redirect(add_query_arg('action', 'installed', wp_get_referer()));
        exit;
    }

    if (isset($_POST['develooper_plugins_delete'])) {
        require_once LOOPIS_DEVELOOPER_DIR . 'functions/develooper_plugins_delete.php';
        develooper_plugins_delete();

        wp_redirect(add_query_arg('action', 'deleted', wp_get_referer()));
        exit;
    }

    if (isset($_POST['develooper_plugins_activate_all'])) {
        develooper_plugin_activate_all();

        wp_redirect(add_query_arg('action', 'activated', wp_get_referer()));
        exit;
    }

    if (isset($_POST['develooper_plugins_deactivate_all'])) {
        develooper_plugin_deactivate_all();

        wp_redirect(add_query_arg('action', 'deactivated', wp_get_referer()));
        exit;
    }
}
/**
 * Handle button activation status and summary display
 * 
 * @param bool $is_all_worked_1
 * @param bool $is_all_worked_2
 * @param array $lists
 * @param string $key1
 * @param string $key2
 * @return array Updated status of all worked variables
 */
function button_activation_handler($is_all_worked_1, $is_all_worked_2, $lists, $key1, $key2)
{
    $all_active = $is_all_worked_1;
    $all_inactive = $is_all_worked_2;

    // Get count of installed plugins
    $count = list_counter($lists, $key1);

    // Get max count of plugins
    $max_plugin_list_count = count($lists);

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

    return [$all_active, $all_inactive];

}

/**
 * Summary of counter. Counts amount of installed/activated plugins
 * @param array $lists
 * @return int
 */
function list_counter($lists, $keywords)
{
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

    return $counter;
}