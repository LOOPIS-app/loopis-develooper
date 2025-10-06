<?php
/*
Plugin Name: LOOPIS Develooper
Plugin URI: https://github.com/LOOPIS-app/loopis-develooper
Description: Plugin providing tools for the developers of LOOPIS.app
Version: 0.2
Author: LOOPIS Develoopers
Author URI: https://loopis.org
*/

// Prevent direct access
if (!defined('ABSPATH')) { 
    exit; 
}

// Define plugin version
define('LOOPIS_DEV_VERSION', '0.2');

// Define plugin folder path constants
define('LOOPIS_DEV_DIR', plugin_dir_path(__FILE__)); // Server-side path to /wp-content/plugins/loopis-develoopers/
define('LOOPIS_DEV_URL', plugin_dir_url(__FILE__)); // Client-side path to https://site.com/wp-content/plugins/loopis-develoopers/

// Include pages
require_once LOOPIS_DEV_DIR . 'pages/develooper_plugins.php';
require_once LOOPIS_DEV_DIR . 'pages/develooper_posts.php';
require_once LOOPIS_DEV_DIR . 'pages/develooper_roles.php';
require_once LOOPIS_DEV_DIR . 'pages/develooper_db_reset.php';

// Admin menu hook
add_action('admin_menu', 'loopis_dev_menu');

// Setup admin menu
function loopis_dev_menu() {
    // Render top level menu item
    add_menu_page(
        'LOOPIS Develooper',      // Page Title
        'LOOPIS Dev.',              // Menu Title
        'manage_options',          // Capability
        'loopis_dev_main',              // Menu Slug
        'develooper_posts_page', // Function - redirect to first submenu
        LOOPIS_DEV_URL . 'assets/img/develooper-dashboard-icon.png'   // Custom Icon 
    );
    
    // Add submenus
    add_submenu_page(
        'loopis_dev_main',         // Parent slug
        'Sample Posts',            // Page title
        'Sample Posts',            // Menu title
        'manage_options',          // Capability
        'develooper_posts',        // Menu slug
        'develooper_posts_page'    // Function
    );
    
    add_submenu_page(
        'loopis_dev_main',         // Parent slug
        'Plugins',                 // Page title
        'Plugins',                 // Menu title
        'manage_options',          // Capability
        'develooper_plugins',      // Menu slug
        'develooper_plugins_page'  // Function
    );
    
    add_submenu_page(
        'loopis_dev_main',         // Parent slug
        'User Roles',              // Page title
        'User Roles',              // Menu title
        'manage_options',          // Capability
        'develooper_roles',        // Menu slug
        'develooper_roles_page'    // Function
    );

    add_submenu_page(
        'loopis_dev_main',         // Parent slug
        'Database Reset',          // Page title
        'Database Reset',          // Menu title
        'manage_options',          // Capability
        'develooper_db_reset',     // Menu slug
        'develooper_db_reset_page' // Function
    );
    
    // Hide the main menu page (but keep the icon and submenus)
    remove_submenu_page('loopis_dev_main', 'loopis_dev_main');
}
