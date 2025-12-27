<?php
/**
 * Add admin menu items for the plugin.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function develooper_admin_menu() {
    // Render top level menu item
    add_menu_page(
        'LOOPIS Develooper',        // Page Title
        'LOOPIS Dev.',              // Menu Title
        'manage_options',           // Capability
        'loopis_dev_main',          // Menu Slug
        'develooper_posts_page',    // Function - redirect to first submenu
        LOOPIS_DEVELOOPER_URL . 'assets/img/develooper-dashboard-icon.png'   // Custom Icon
    );
    
    // Add submenus
    add_submenu_page(
        'loopis_dev_main',         // Parent slug
        'Develooper',              // Page title
        'Develooper',              // Menu title
        'manage_options',          // Capability
        'develooper',              // Menu slug
        'develooper_page'          // Function
    );

    add_submenu_page(
        'loopis_dev_main',         // Parent slug
        'Samples',                 // Page title
        'Samples',                 // Menu title
        'manage_options',          // Capability
        'develooper_samples',      // Menu slug
        'develooper_samples_page'  // Function
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
    
    // Hide the main menu page (but keep the icon and submenus)
    remove_submenu_page('loopis_dev_main', 'loopis_dev_main');
}
