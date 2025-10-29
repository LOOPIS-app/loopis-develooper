<?php
/**
 * Function to create LOOPIS sample users in the WordPress database.
 * 
 * This file is included from the WP admin page with the same name.
 * 
 * @package LOOPIS_Develooper
 * @subpackage Dev-tools
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Import sample lists
require_once LOOPIS_DEVELOOPER_DIR .'functions/sample.php';

// Include WP functions
require_once(ABSPATH.'wp-admin/includes/user.php');

/**
 * Inserts users into wp_users
 * 
 * @return void
 */
function develooper_users_insert() {

    loopis_elog_function_start('develooper_sample_user_insert');

    $inserted_users = []; // Array to hold details of inserted users

    // Fetch sample users from sample.php
    $sample_users = get_sample_users();

    foreach ($sample_users as $user) {

        // Check if user already exists
        if (username_exists($user['user_login'])) {
            continue; // Skip existing users
        }

        // Insert user
        $user_id = wp_insert_user([
            'user_login'    => $user['user_login'],
            'user_pass'     => $user['user_pass'],
            'user_email'    => $user['user_email'],
            'display_name'  => $user['display_name'],
            'user_nicename' => $user['user_nicename'],
            'first_name'    => $user['first_name'],
            'last_name'     => $user['last_name']
        ]);

        if (is_wp_error($user_id)) {
            loopis_elog_first_level('Failed to create user ' . $user['user_login'] . ': ' . $user_id->get_error_message());
            continue;
        }

        // Set roles
        $user_id = new WP_User($user_id);
        foreach($user['role'] as $role){
            $user_id->set_role($role);
        }

        if (!is_wp_error($user_id)) {
            $inserted_users[] = [
                'user_login'   => $user['user_login'],
                'user_email'   => $user['user_email'],
                'display_name' => $user['display_name'],
                'role'         => $user['role']
            ];
        } else {
            loopis_elog_first_level('Failed to assign role to user ' . $user['user_login'] . ': ' . $user_id->get_error_message());
        }
    }

    loopis_elog_function_end_success('develooper_sample_user_insert');
}