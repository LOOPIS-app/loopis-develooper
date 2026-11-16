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
require_once __DIR__ . '/content/users.php';

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

    // Fetch sample users from users.php
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
            'display_name'  => trim($user['first_name'] . ' ' . $user['last_name']),
            'user_nicename' => $user['user_nicename'],
            'first_name'    => $user['first_name'],
            'last_name'     => $user['last_name']
        ]);

        // Check for errors. If user creation failed, log the error and skip.
        if (is_wp_error($user_id)) {
            loopis_elog_first_level('Failed to create user ' . $user['user_login'] . ': ' . $user_id->get_error_message());
            $inserted_users['failed'][] = $user['user_login'];
            continue;
        }

        if (is_multisite() && !is_main_site()) {
            $site_add_failed = false;
            foreach ([get_current_blog_id(), get_main_site_id()] as $site_id) {
                $result = add_user_to_blog($site_id, $user_id, $user['role'][0]);
                if (is_wp_error($result)) {
                    loopis_elog_first_level('User created but could not be added to site ' . $site_id . ': ' . $user['user_login'] . ': ' . $result->get_error_message());
                    $site_add_failed = true;
                    continue;
                }

                $additional_roles = array_slice($user['role'], 1);
                if ($additional_roles) {
                    switch_to_blog($site_id);
                    $wp_user = new WP_User($user_id);
                    foreach ($additional_roles as $role) {
                        $wp_user->add_role($role);
                    }
                    restore_current_blog();
                }
            }
            if ($site_add_failed) {
                $inserted_users['failed'][] = $user['user_login'];
                continue;
            }
        } else {
            $wp_user = new WP_User($user_id);
            $wp_user->set_role($user['role'][0]);
            foreach (array_slice($user['role'], 1) as $role) {
                $wp_user->add_role($role);
            }
        }

        $inserted_users['inserted'][] = $user['user_login'];
        loopis_elog_first_level('Assigned roles to user: ' . $user['user_login']);
    }

    if (empty($inserted_users['failed'])) {
        loopis_elog_function_end_success('develooper_sample_user_insert');
    } else {
        loopis_elog_function_end_failure('develooper_sample_user_insert');
    }

    return $inserted_users;
}