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

// Include functions
require_once LOOPIS_DEVELOOPER_DIR .'functions/sample.php';

// Include WP functions
require_once(ABSPATH.'wp-admin/includes/user.php');

/**
 * Delete all LOOPIS users but admin from wp_users
 * 
 * @return void
 */
function loopis_users_delete() {
    loopis_elog_function_start('loopis_users_delete');

    $sample_users = get_sample_users();
    global $wpdb;
    // Get all users except user 1(admin)
    $users = get_users(['exclude' => [1]]);
    foreach ($users as $user) {
        // Delete each user
        wp_delete_user($user->ID);
    }
    // Resets user count
    $wpdb->query("ALTER TABLE {$wpdb->users} AUTO_INCREMENT = 1");
    loopis_elog_function_end_success('loopis_users_delete');
}