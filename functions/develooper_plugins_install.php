<?php
/**
 * Function to install LOOPIS plugin dependencies.
 *
 * This function is called by main function 'loopis_db_setup'.
 * 
 * @package LOOPIS_Config
 * @subpackage Plugins
 */

// Prevent direct access
if (!defined('ABSPATH')) { 
    exit; 
}

// Possibly necessary dependencies as WordPress does not always autoload the following functions
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/misc.php';

// Import plugin list
require_once LOOPIS_DEVELOOPER_DIR . 'assets/plugins/plugin_list.php';

/**
 * Installs plugins in wp-content/plugins/
 * 
 * @return void
 */
function develooper_plugins_install(){
    loopis_elog_function_start('develooper_plugins_install');
    // Plugin list
    $plugins = plugin_list();

    // Helps avoid internal installer wp_die
    if (!class_exists('Loopis_Silent_Skin')) {
        class Loopis_Skin extends WP_Upgrader_Skin {
            public function header() {}
            public function footer() {}
            public function feedback($string, ...$args) {}
            public function error($errors) {}
            public function before() {}
            public function after() {}
        }
    }
    // Get upgrader
    $upgrader = new Plugin_Upgrader( new Loopis_Skin() );

    foreach ( $plugins as $plugin ) {
        
        // Redefinitions because its good practice
        $slug     = $plugin['slug'];
        $main     = $plugin['main'];
        $plugin_dir = WP_PLUGIN_DIR . '/' . $slug;

        loopis_elog_first_level("INSTALLING: {$slug}...");

        // Is it running already?
        if ( ! is_plugin_active( $main ) ) {

            // Does it exist already?
            if ( ! file_exists( $plugin_dir ) ) {

                // Get download link from WordPress
                $api = plugins_api( 'plugin_information', [
                    'slug'   => $slug,
                    'fields' => [ 'sections' => false ], // No extra stuff like readmes etc.
                ] );

                // Could we get the wordpress download link?
                if ( ! is_wp_error( $api ) && isset( $api->download_link ) ) {
                    // Install
                    $result = $upgrader->install( $api->download_link );
                
                }
            }
        }
    }
    loopis_elog_function_end_success('develooper_plugins_install');
}

function develooper_plugin_install($slug, $main) {
    // Helps avoid internal installer wp_die
    if (!class_exists('Loopis_Silent_Skin')) {
        class Loopis_Skin extends WP_Upgrader_Skin {
            public function header() {}
            public function footer() {}
            public function feedback($string, ...$args) {}
            public function error($errors) {}
            public function before() {}
            public function after() {}
        }
    }

    // Get upgrader
    $upgrader = new Plugin_Upgrader( new Loopis_Skin() );

    $plugin_dir = WP_PLUGIN_DIR . '/' . $slug;

    loopis_elog_first_level("INSTALLING: {$slug}...");

    // Is it running already?
    if ( ! is_plugin_active( $main ) ) {

        // Does it exist already?
        if ( ! file_exists( $plugin_dir ) ) {

            // Get download link from WordPress
            $api = plugins_api( 'plugin_information', [
                'slug'   => $slug,
                'fields' => [ 'sections' => false ], // No extra stuff like readmes etc.
            ] );

            // Could we get the wordpress download link?
            if ( ! is_wp_error( $api ) && isset( $api->download_link ) ) {
                // Install
                $result = $upgrader->install( $api->download_link );
            
            }
        }
    }
}