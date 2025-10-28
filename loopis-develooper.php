<?php
/*
Plugin Name: LOOPIS Develooper
Plugin URI: https://github.com/LOOPIS-app/loopis-develooper
Description: Plugin providing tools for the developers of LOOPIS.app
<<<<<<< Updated upstream
Version: 0.31
Author: LOOPIS Develoopers
=======
Version: 0.4
Author: The Develoopers
>>>>>>> Stashed changes
Author URI: https://loopis.org
Required Plugins: LOOPIS Config
*/

// Prevent direct access
if (!defined('ABSPATH')) { 
    exit; 
}

// Define plugin version
<<<<<<< Updated upstream
define('LOOPIS_DEVELOOPER_VERSION', '0.31');
=======
define('LOOPIS_DEVELOOPER_VERSION', '0.4');
>>>>>>> Stashed changes

// Define plugin folder path constants
define('LOOPIS_DEVELOOPER_DIR', plugin_dir_path(__FILE__));    // Server-side path to /wp-content/plugins/loopis-develooper/
define('LOOPIS_DEVELOOPER_URL', plugin_dir_url(__FILE__));     // Client-side path to https://site.com/wp-content/plugins/loopis-develooper/

// Define folders to include (for admins in admin area)
function develooper_load_files() {
    if (!current_user_can('administrator') || !is_admin()) {
        return; // Exit early
    }

    develooper_include_folder('interface');
    develooper_include_folder('pages');
}

// Function to include all PHP files in a folder
function develooper_include_folder($folder_name) {
    $absolute_path = LOOPIS_DEVELOOPER_DIR . '/' . $folder_name;
    if (is_dir($absolute_path)) {
        foreach (glob($absolute_path . '/*.php') as $file) {
            include_once $file;
        }
    } else {
        error_log("develooper-plugin: Failed to include folder from develooper-plugin.php: {$folder_name}");
    }
}

// Admin menu hook
add_action('admin_menu', 'develooper_admin_menu');

// Load files when all plugins are loaded
add_action('plugins_loaded', 'develooper_load_files');