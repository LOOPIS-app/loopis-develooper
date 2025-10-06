<?php
/** 
 * Main function for resetting the LOOPIS configuration.
 *
 * WARNING! This tool is intended for development purposes only.
 * Use with caution and only in a safe development environment!
 * 
 * @package LOOPIS_Develoopers
 * @subpackage Dev-tools
 */


// Include the necessary files
require_once LOOPIS_DEV_DIR . 'functions/db-reset/loopis_pages_delete.php';
require_once LOOPIS_DEV_DIR . 'functions/db-reset/loopis_categories_delete.php';
require_once LOOPIS_DEV_DIR . 'functions/db-reset/loopis_tags_delete.php';
require_once LOOPIS_DEV_DIR . 'functions/db-reset/loopis_user_roles_delete.php';
require_once LOOPIS_DEV_DIR . 'functions/db-reset/loopis_users_delete.php';
require_once LOOPIS_DEV_DIR . 'functions/db-reset/loopis_plugins_delete.php';

// Define the function
function develooper_db_reset() {

    // Access WordPress database object
    global $wpdb;

    // Define custom table names
    $lockers_table = $wpdb->prefix . 'loopis_lockers';
    $settings_table = $wpdb->prefix . 'loopis_settings';

    // Drop LOOPIS custom tables
    $wpdb->query("DROP TABLE IF EXISTS $lockers_table");
    $wpdb->query("DROP TABLE IF EXISTS $settings_table");
}
