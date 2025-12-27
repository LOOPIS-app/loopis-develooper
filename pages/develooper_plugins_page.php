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
function develooper_plugins_page() {
    ?>
    <div class="wrap">
        <!-- Page title and description-->
        <h1>🧩 Develooper plugins <span class="h1-right">Version <?php echo esc_html(LOOPIS_DEVELOOPER_VERSION); ?></span></h1>
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
        default_button_color_style();
        ?>

        <!-- Page content-->
        <?php
        echo '<form id="plugin-form" method="POST">';
        $is_all_installed = false;
        $is_all_uninstalled = true;
        $is_all_activated = false;
        $is_all_deactivated = true;

        require_once LOOPIS_DEVELOOPER_DIR . 'assets/plugins/plugin_list.php';

        $plugins = plugin_list();

        list( $is_all_installed, $is_all_uninstalled ) = button_activation_handler( $is_all_installed, $is_all_uninstalled, $plugins, 'install', 'uninstall' );
        list( $is_all_activated, $is_all_deactivated ) = button_activation_handler( $is_all_activated, $is_all_deactivated, $plugins, 'activate', 'deactivate' );

        $disable_install = $is_all_installed? 'disabled ' : '';
        $disable_uninstall = $is_all_uninstalled? 'disabled ': '';
        $disable_activate = '';
        $disable_deactivate = '';

        if ( $is_all_uninstalled ) {
            $disable_activate = 'disabled';
            $disable_deactivate = 'disabled';
        } else {
            if ( $is_all_activated ) {
                $disable_activate = 'disabled';
            } else if ( $is_all_deactivated ) {
                $disable_deactivate = 'disabled';
            }
        }


        echo '<button ' . $disable_install . ' class="button button-primary bunch-action-btn submit-btn" type="submit" name="develooper_plugins_install">Install all plugins</button>';
        echo '<button ' . $disable_uninstall . ' class="button button-primary bunch-action-btn cancel-btn" type="submit" name="develooper_plugins_delete">Uninstall all plugins</button>';
        echo '<button ' . $disable_activate . ' class="button button-primary bunch-action-btn activate-btn" type="submit" name="develooper_plugins_activate_all">Activate all plugins</button>';
        echo '<button ' . $disable_deactivate . ' class="button button-primary bunch-action-btn deactivate-btn" type="submit" name="develooper_plugins_deactivate_all">Deactivate all plugins</button>';
        echo '<div class ="loader" id="loader"></div>';
        echo '</form>';
        

        render_user_roles_styles();
        plugin_table_style();

        render_loopis_plugins_table();
        activate_loader('.bunch-action-btn', 'loader', 'plugin-form');
        activate_loader('.seperate-action-btn', 'loader', 'seperate-plugin-form');
        ?>
    </div>
<?php
}

function setup() {
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

function button_activation_handler($is_all_worked_1, $is_all_worked_2, $lists, $key1, $key2) {
    $all_active = $is_all_worked_1;
    $all_inactive = $is_all_worked_2;

    $count = list_counter($lists, $key1);
    $max_plugin_list_count = count($lists);

    if ($count == $max_plugin_list_count) {
            $all_active = true;
            $all_inactive = false;
            echo '<p>All ' . $key1 . 'ed. ' . $key1 . ': ' . $all_active . ' ' . $key2 . ': ' . $all_inactive. '</p>';
        } else if ($count > 0 && $count < $max_plugin_list_count) {
            echo '<p>Some ' . $key1 . 'ed some not. ' . $key1 . ': ' . $all_active . ' ' . $key2 . ':' . $all_inactive. '</p>';
            $all_active = false;
            $all_inactive = false;
        } else {
            echo '<p>None ' . $key1 . 'ed. ' . $key1 . ': ' . $all_active . ' ' . $key2 . ': ' . $all_inactive. '</p></p>';
            $all_active = false;
            $all_inactive = true;
        }

    return [$all_active, $all_inactive];

}

/**
 * Summary of counter. Count each 
 * @param array $lists
 * @return int
 */
function list_counter($lists, $keywords) {
    $counter = 0;
    foreach ($lists as $plugin) {
            if ($keywords == 'install') {
            $plugin_path = WP_PLUGIN_DIR . '/' . $plugin['slug']; 
            if (file_exists($plugin_path)) {
                $counter++;
            }
        } else if ($keywords == 'activate') {
            $plugin_main = $plugin['main'];
            if (is_plugin_active($plugin_main)) {
                $counter++;
            }
        }
    }

    return $counter;
}