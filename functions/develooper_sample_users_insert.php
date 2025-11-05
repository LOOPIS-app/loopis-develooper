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
require_once LOOPIS_DEVELOOPER_DIR .'assets/sample_users/labels/sample-users.php';

// Include WP functions
require_once(ABSPATH.'wp-admin/includes/user.php');

/**
 * Inserts users into wp_users
 * 
 * @return array $inserted_users Details of inserted users
 */
function develooper_users_insert() {

    // Start logging
    loopis_elog_function_start('develooper_sample_user_insert');

    $inserted_users = [
        'inserted' => [],
        'failed'   => [],
        'existed'=> [],
    ]; // Array to hold details of inserted users

    // Fetch sample users from sample-users.php
    $sample_users = get_sample_users();

    // Loop through each sample user and insert into the database
    foreach ($sample_users as $user) {

        // Check if user already exists
        if (username_exists($user['user_login'])) {
            loopis_elog_first_level('User already exists: ' . $user['user_login']);
            $inserted_users['existed'][] = $user['user_login'];
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

        // Check for errors. If user creation failed, log the error and skip.
        if (is_wp_error($user_id)) {
            loopis_elog_first_level('Failed to create user ' . $user['user_login'] . ': ' . $user_id->get_error_message());
            $inserted_users['failed'][] = $user['user_login'];
            continue;
        } else {
            $inserted_users['inserted'][] = $user['user_login'];
        }

        // Set roles
        $user_id = new WP_User($user_id);
        foreach($user['role'] as $role){
            $user_id->set_role($role);
        }

        // if user role assignment succeeded, report log 
        if (!is_wp_error($user_id)) {
            loopis_elog_first_level('Assigned role to user: ' . $user['user_login']);
        } else {
            // else report error
            loopis_elog_first_level('Failed to assign role to user ' . $user['user_login'] . ': ' . $user_id->get_error_message());
        }
    }

    //final log
    loopis_elog_function_end_success('develooper_sample_user_insert');


    return $inserted_users;
}
