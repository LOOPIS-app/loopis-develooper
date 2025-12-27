<?php


// Prevent direct access
if (!defined('ABSPATH')) { 
    exit; 
}

require_once LOOPIS_DEVELOOPER_DIR . 'assets/plugins/plugin_list.php';

if (isset($_POST['develooper_plugin_install'])) {
    if ( ! current_user_can('activate_plugins') ) {
        wp_die(__('Insufficient permissions', 'loopis'));
    }

    $payload = sanitize_text_field( wp_unslash( $_POST['develooper_plugin_install'] ) );
    list( $slug, $main ) = array_pad( explode('||', $payload, 2), 2, '' );

    if ( $slug && $main ) {
        require_once LOOPIS_DEVELOOPER_DIR . 'functions/develooper_plugins_install.php';
        require_once LOOPIS_DEVELOOPER_DIR . 'functions/develooper_plugins_delete.php';

        $plugin_dir = WP_PLUGIN_DIR . '/' . $slug;
        if ( ! is_dir( $plugin_dir ) ) {
            develooper_plugin_install( $slug, $main );
        } else {
            develooper_plugin_delete($slug, $main);
        }
    }

    wp_redirect(add_query_arg('action', 'installed', wp_get_referer()));
    exit;
}

if (isset($_POST['develooper_plugin_activate'])) {
    if ( ! current_user_can('activate_plugins') ) {
        wp_die(__('Insufficient permissions', 'loopis'));
    }

    $payload = sanitize_text_field( wp_unslash( $_POST['develooper_plugin_activate'] ) );
    list( $slug, $main ) = array_pad( explode('||', $payload, 2), 2, '' );

    if ( $slug && $main ) {
        if (! is_plugin_active($main)) {
            activate_plugins($main);
        } else {
            deactivate_plugins($main);
        }
    }

    wp_redirect(add_query_arg('action', 'activated', wp_get_referer()));
    exit;
}

function render_loopis_plugins_table() {
    //$roles = wp_roles()->get_names();

    $installed_plugins = get_plugins();

    $plugins = plugin_list();

    
    echo '<div class="roles-section margin-top-table">';

    // Wrap table in a form so the button can submit the selected plugin slug|main

    echo '<table class="wp-list-table widefat fixed striped loopis-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th scope="col" class="capability-header fit-content-col">Status</th>';
    echo '<th scope="col" class = "expand-col">Plugin name</th>';
    echo '<th scope="col" class = "button-col">Installations</th>';
    echo '<th scope="col" class = "button-col">Activations</th>';
    
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($plugins as $plugin) {
        //$role = get_role($role_key);
        //if (!$role) continue;
        
        //get slug of a plugin
        $get_plugin_slug = $plugin['slug'];
        echo '<tr>';

        //$has_capability = isset($role->capabilities[$cap]) && $role->capabilities[$cap];
        $plugin_dir = WP_PLUGIN_DIR . '/' . $get_plugin_slug;
        $has_plugin_installed = is_dir($plugin_dir);
        //Shift between ✅ and ❌ depending on ***$has_plugin_installed*** variable
        $status = $has_plugin_installed ? '✅' : '❌';
        $class = $has_plugin_installed ? 'cap-granted' : 'cap-denied';
            
        echo '<td><span class="capability-status ' . $class . '">' . $status . '</span></td>';
        echo '<td><span class="role-name">' . esc_html($get_plugin_slug) . '</span></td>';
        
        $disable_install = $has_plugin_installed ? 'disabled' : '';
        $disable_uninstall = $has_plugin_installed ? '' : 'disabled';
        $disable_activate = 'disabled';
        $disable_deactivate = 'disabled';
        if ( $has_plugin_installed ) {
            if ( is_plugin_active( $plugin['main'] ) ) {
                $disable_deactivate = '';
            } else {
                $disable_activate = '';
            }
        }

        
        

        $button_value = esc_attr( $plugin['slug'] . '||' . $plugin['main'] );

        echo '<form id="seperate-plugin-form" method="post">';
        echo '<td>';
        echo '<button ' . $disable_install . ' class="button button-primary seperate-action-btn submit-btn" type="submit" name="develooper_plugin_install" value="' . $button_value . '">Install</button>';
        echo '<button ' . $disable_uninstall . ' class="button button-primary seperate-action-btn cancel-btn" type="submit" name="develooper_plugin_install" value="' . $button_value . '">Uninstall</button>';
        echo '</td>';
        echo '</form>';
        

       
        echo '<form method="post">';
        echo '<td>';
        echo '<button '. $disable_activate .' class="button button-primary activate-btn seperate-action-btn" type="submit" name="develooper_plugin_activate" value="' . $button_value . '">Activate</button>';
        echo '<button '. $disable_deactivate .' class="button button-primary deactivate-btn seperate-action-btn" type="submit" name="develooper_plugin_activate" value="' . $button_value . '">Deactivate</button>';
        echo '</td>';
        echo '</form>';
        
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    echo '</div>';
}


function plugin_table_style() {
    ?>
    <style>
        .responsive-table {
            width: 100%; /* Table takes full available width */
            table-layout: auto; /* Columns adjust based on content and width rules */
            border-collapse: collapse; /* Optional: for cleaner borders */
        }

        /* Apply width properties to both th and td for consistency */
        .fit-content-col {
            width: 5%; /* Forces the column to take minimum space required for content */
            white-space: nowrap; /* Prevents content from wrapping, ensuring a minimal width */
        }

        .button-col {
            width: 10%; /* Forces the column to take minimum space required for content */
            white-space: nowrap; /* Prevents content from wrapping, ensuring a minimal width */
        }

        .expand-col {
            width: auto; /* Takes up the remaining available space */
        }

        .seperate-action-btn {
            margin: 0.2% !important;
            width: 30% !important;
            font-size: 0% !important;
            height: 3% !important;
            white-space: nowrap !important;
            transition: width 0.5s ease-in-out !important;
        }

        .seperate-action-btn:hover { 
            width: 60% !important;
            overflow: hidden !important;
            font-size: inherit !important;
        }

        /* Optional: add some basic styling */
        .responsive-table, .responsive-table th, .responsive-table td {
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
