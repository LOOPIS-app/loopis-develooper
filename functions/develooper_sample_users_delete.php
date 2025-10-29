<?php
/**
 * Function to delete LOOPIS users in the WordPress database.
 *
 * This file is included from the WP admin page with the same name. (When implemented)
 *
 * @package LOOPIS_Config
 * @subpackage Dev-tools
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Import sample lists
require_once LOOPIS_DEVELOOPER_DIR .'functions/labels/sample-users.php';

// Include WP functions
require_once(ABSPATH.'wp-admin/includes/user.php');

/**
 * Delete all LOOPIS users but admin from wp_users
 * 
 * @return void
 */
function loopis_users_delete() {
    loopis_elog_function_start('loopis_users_delete');

    // Fetch sample users from sample-users.php
    $sample_users = get_sample_users();

    global $wpdb;
    foreach ($sample_users as $sample_user) {
        // Get user by login
        $users = get_user_by('login', $sample_user['user_login']);

        // If user exists, delete
        if (!empty($users)) {
            // Delete each user
            wp_delete_user($users->ID);
            loopis_elog_first_level('Deleted user: ' . $sample_user['user_login'] . ' (ID: ' . $users->ID . ')');
        } else {
            loopis_elog_first_level('User not found: ' . $sample_user['user_login']);
        }
    }
    // Resets user count
    $wpdb->query("ALTER TABLE {$wpdb->users} AUTO_INCREMENT = 1");
    loopis_elog_function_end_success('loopis_users_delete');
}