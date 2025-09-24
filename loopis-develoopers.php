<?php
/*
Plugin Name: LOOPIS Develoopers
Plugin URI: https://github.com/LOOPIS-app/loopis-develoopers
Description: Plugin providing tools for the developers of LOOPIS.app
Version: 0.0
Author: develoopers
Author URI: https://loopis.org
*/

// Prevent direct access
if (!defined('ABSPATH')) { 
    exit; 
}

// Define plugin version
define('LOOPIS_DEV_VERSION', '0.0');

// Define plugin folder path constants
define('LOOPIS_DEV_DIR', plugin_dir_path(__FILE__)); // Server-side path to /wp-content/plugins/loopis-develoopers/
define('LOOPIS_DEV_URL', plugin_dir_url(__FILE__)); // Client-side path to https://site.com/wp-content/plugins/loopis-develoopers/

// Start of error log
error_log("===== Start: LOOPIS Develoopers =====");
error_log("Plugin version: " . LOOPIS_DEV_VERSION);

// Include neccessary files
require_once LOOPIS_DEV_DIR . 'pages/loopis_dev_page.php';

// Admin menu hook
add_action('admin_menu', 'loopis_dev_menu');

// Setup admin menu
function loopis_dev_menu() {
    //Render top level menu item
    add_menu_page(
        'LOOPIS Develoopers',      // Page Title
        'LOOPIS Dev.',              // Menu Title
        'manage_options',          // Capability
        'loopis_dev',              // Menu Slug
        'loopis_dev_page',         // Function to display the page (change if submenus included)
        LOOPIS_DEV_URL . 'assets/img/develoopers-dashboard-icon.png'   // Develoopers Icon 
    );
}

// End of error log
error_log("===== End: LOOPIS Develoopers =====");