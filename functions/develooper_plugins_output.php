<?php


// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

require_once LOOPIS_DEVELOOPER_DIR . 'assets/plugins/plugin_list.php';


/**
 * Install or delete a plugin based on its slug and main file.
 * 
 * @return void
 */
if (isset($_POST['develooper_plugin_install'])) {

    if (!current_user_can('activate_plugins')) {
        wp_die(__('Insufficient permissions', 'loopis'));
    }

    // Sanitize and extract slug and main file
    $payload = sanitize_text_field(wp_unslash($_POST['develooper_plugin_install']));

    // Split payload into slug and main file
    list($slug, $main) = array_pad(explode('||', $payload, 2), 2, '');

    // Proceed only if both slug and main file are provided
    if ($slug && $main) {
        require_once LOOPIS_DEVELOOPER_DIR . 'functions/develooper_plugins_install.php';
        require_once LOOPIS_DEVELOOPER_DIR . 'functions/develooper_plugins_delete.php';

        $plugin_dir = WP_PLUGIN_DIR . '/' . $slug;

        // Check if plugin is installed; if not, install it, else delete it
        if (!is_dir($plugin_dir)) {
            develooper_plugin_install($slug, $main);
        } else {
            develooper_plugin_delete($slug, $main);
        }
    }

    // Redirect back to the referring page with action parameter
    wp_redirect(add_query_arg('action', 'installed', wp_get_referer()));
    exit;
}


/**
 * Activate or deactivate a plugin based on its slug and main file.
 * 
 * @return void
 */
if (isset($_POST['develooper_plugin_activate'])) {
    loopis_elog_function_start('develooper_plugin_activations');

    if (!current_user_can('activate_plugins')) {
        wp_die(__('Insufficient permissions', 'loopis'));
    }

    $payload = sanitize_text_field(wp_unslash($_POST['develooper_plugin_activate']));
    list($slug, $main) = array_pad(explode('||', $payload, 2), 2, '');

    if ($slug && $main) {

        // Check if plugins activated. If not, activate. If yes, deactivate.
        if (!is_plugin_active($main)) {
            loopis_elog_first_level(" Activating plugin: {$slug}...");
            activate_plugins($main);
        } else {
            loopis_elog_first_level(" Deactivating plugin: {$slug}...");
            deactivate_plugins($main);
        }
    }

    loopis_elog_function_end_success('develooper_plugin_activations');

    wp_redirect(add_query_arg('action', 'activated', wp_get_referer()));
    exit;
}

/**
 * Render the plugins table with plugins from plugin_list()
 * 
 * @return void
 */

function render_loopis_plugins_table()
{
    //$roles = wp_roles()->get_names();

    $installed_plugins = get_plugins();

    // Fetch plugin lists from plugin_list() (at assets/plugins/plugin_list.php)
    $plugins = plugin_list();


    echo '<div class="roles-section margin-top-table">';

    // Wrap table in a form so the button can submit the selected plugin slug|main

    echo '<table class="wp-list-table widefat fixed striped loopis-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th scope="col" class="capability-header fit-content-col">Status</th>';
    echo '<th scope="col" class = "expand-col">Plugin name</th>';
    echo '<th scope="col" class = "capability-header button-col">Buttons</th>';

    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($plugins as $plugin) {

        //get slug of a plugin
        $get_plugin_slug = $plugin['slug'];
        echo '<tr>';


        $plugin_dir = WP_PLUGIN_DIR . '/' . $get_plugin_slug;
        $has_plugin_installed = is_dir($plugin_dir);

        //Shift between ✅, ⏸ and ❌ depending on ***$has_plugin_installed*** variable

        /**
         * Status symbols:
         * ❌ - Not installed
         * ✅ - Installed and active
         * ⏸ or &#9208; - Installed but inactive
         */

        $status = '';

        if (!$has_plugin_installed) {
            $status = '❌';

        } else {
            $status = is_plugin_active($plugin['main']) ? '✅' : '&#9208;';
        }


        $class = $has_plugin_installed ? 'cap-granted' : 'cap-denied';

        echo '<td><span class="capability-status ' . $class . ' symbol-size">' . $status . '</span></td>';
        echo '<td><span class="role-name">' . esc_html($get_plugin_slug) . '</span></td>';

        // Disable buttons based on installation and activation status
        $disable_install = $has_plugin_installed ? 'disabled' : '';
        $disable_uninstall = $has_plugin_installed ? '' : 'disabled';
        $disable_activate = 'disabled';
        $disable_deactivate = 'disabled';

        // Enable activation buttons when condition met
        if ($has_plugin_installed) {
            if (is_plugin_active($plugin['main'])) {
                $disable_deactivate = '';
            } else {
                $disable_activate = '';
            }
        }




        $button_value = esc_attr($plugin['slug'] . '||' . $plugin['main']);

        // Button form for individual plugins
        echo '<form method="post" id="plugin-form" onsubmit="button_loading(this)">';
        echo '<td>';
        echo '<button ' . $disable_install . ' class="button button-primary seperate-action-btn loading-btn wp-blue" type="submit" name="develooper_plugin_install" value="' . $button_value . '">Install</button>';
        echo '<button ' . $disable_uninstall . ' class="button button-primary seperate-action-btn loading-btn wp-red" type="submit" name="develooper_plugin_install" value="' . $button_value . '">Delete</button>';
        echo '<button ' . $disable_activate . ' class="button button-primary seperate-action-btn loading-btn wp-green" type="submit" name="develooper_plugin_activate" value="' . $button_value . '">Activate</button>';
        echo '<button ' . $disable_deactivate . ' class="button button-primary seperate-action-btn loading-btn wp-orange" type="submit" name="develooper_plugin_activate" value="' . $button_value . '">Deactivate</button>';
        echo '</td>';
        echo '</form>';

        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';

    echo '</div>';
}

/** Render the plugins table with plugins from plugin_list() */

function plugin_table_style()
{
    ?>
    <style>
        .responsive-table {
            width: 100%;
            /* Table takes full available width */
            table-layout: auto;
            /* Columns adjust based on content and width rules */
            border-collapse: collapse;
            /* Optional: for cleaner borders */
        }

        /* Apply width properties to both th and td for consistency */
        .fit-content-col {
            width: 5%;
            /* Forces the column to take minimum space required for content */
            white-space: nowrap;
            /* Prevents content from wrapping, ensuring a minimal width */
        }

        .button-col {
            width: 25%;
            /* Forces the column to take minimum space required for content */
            white-space: nowrap;
            /* Prevents content from wrapping, ensuring a minimal width */
        }

        .expand-col {
            width: auto;
            /* Takes up the remaining available space */
        }

        .symbol-size {
            font-size: 150%;
        }

        .seperate-action-btn {
            width: auto !important;
            margin: 0.5% !important;
        }


        /* Optional: add some basic styling */
        .responsive-table,
        .responsive-table th,
        .responsive-table td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }

        .margin-top-table {
            margin-top: 0.5%;
        }
    </style>
    <?php
}
