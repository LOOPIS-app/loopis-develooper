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
require_once LOOPIS_DEVELOOPER_DIR . 'functions/develooper_plugins_activate.php';
require_once LOOPIS_DEVELOOPER_DIR . 'functions/develooper_plugins_deactivate.php';
require_once LOOPIS_DEVELOOPER_DIR . 'functions/develooper_plugins_button_activation_handler.php';

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
                echo '<div class="notice notice-success is-dismissible"><p>❌ Plugins have been successfully deleted!</p></div>';
            } else if ($_GET['action'] === 'activated') {
                echo '<div class="notice notice-success is-dismissible"><p>✅ Plugins have been successfully activated!</p></div>';
            } else if ($_GET['action'] === 'deactivated') {
                echo '<div class="notice notice-success is-dismissible"><p>❌ Plugins have been successfully deactivated!</p></div>';
            }
        }
        ?>
        <!-- Page content-->
        <?php
        echo '<form id="plugin-form" method="POST" onsubmit="button_loading(this)">';
        $is_all_installed = false;
        $is_all_uninstalled = true;
        $is_all_activated = false;
        $is_all_deactivated = false;

        require_once LOOPIS_DEVELOOPER_DIR . 'assets/plugins/plugin_list.php';

        //fetch plugin lists
        $plugins = plugin_list();

        // Determine button states
        list($is_all_installed, $is_all_uninstalled) = button_activation_handler($is_all_installed, $is_all_uninstalled, $plugins, 'install');

        if (!$is_all_uninstalled) {
            list($is_all_activated, $is_all_deactivated) = button_activation_handler($is_all_activated, $is_all_deactivated, $plugins, 'activate');
        }
        // Button display logic
        // If all installed, disable install button, opposite for uninstall. 
        $disable_install = $is_all_installed ? 'disabled ' : '';
        $disable_uninstall = $is_all_uninstalled ? 'disabled ' : '';

        // Logic for activate/deactivate buttons
        $disable_activate = $is_all_activated ? 'disabled ' : '';
        $disable_deactivate = $is_all_deactivated ? 'disabled ' : '';

        // Render plugin buttons for all plugins
        echo '<button ' . $disable_install . ' class="button button-primary all-action-btn loading-btn wp-blue" type="submit" name="develooper_plugins_install">Install all plugins</button>';
        echo '<button ' . $disable_uninstall . ' class="button button-primary all-action-btn loading-btn wp-red" type="submit" name="develooper_plugins_delete">Delete all plugins</button>';
        echo '<button ' . $disable_activate . ' class="button button-primary all-action-btn loading-btn wp-green" type="submit" name="develooper_plugins_activate_all">Activate all plugins</button>';
        echo '<button ' . $disable_deactivate . ' class="button button-primary all-action-btn loading-btn wp-orange" type="submit" name="develooper_plugins_deactivate_all">Deactivate all plugins</button>';
        // loading display when buttons are clicked
        echo '<div class="coin" id="coin"><img src="' . LOOPIS_DEVELOOPER_URL . 'assets/img/coin.png" alt="Loading..."></div>';
        //echo '<div class ="loader" id="loader"></div>';
        echo '</form>';


        render_user_roles_styles();
        // Render the plugins table style
        plugin_table_style();

        // Render the plugins table with plugins
        render_loopis_plugins_table();
        // loader display activations
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
