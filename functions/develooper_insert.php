<?php
/**
 * Function to create a LOOPIS develooper user in the WordPress database.
 *
 * This file is included from the WP admin page with the same name. (When implemented)
 *
 * @package LOOPIS_Develooper
 * @subpackage Dev-tools
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Inserts users into wp_users
 * 
 * @return void
 */
function develooper_insert() {    
    loopis_elog_function_start('develooper_insert');

    // Choose one user...
    $dev_users = [
        [
            'user_login'    => 'johan-hagvil',
            'user_nicename' => 'johan-hagvil',
            'user_email'    => 'johan.hagvil@gmail.com',
            'user_pass'     => 'develoop3r',
            'role'          => ['member','develooper'],
            'display_name'  => 'Johan-Hagvil',
            'first_name'    => 'Johan',
            'last_name'     => 'Hagvil',
        ],
        [
            'user_login'    => 'johan-linger',
            'user_nicename' => 'johan-linger',
            'user_email'    => 'linger.konsult@gmail.com',
            'user_pass'     => 'develoop3r',
            'role'          => ['member','develooper'],
            'display_name'  => 'Johan-Linger',
            'first_name'    => 'Johan',
            'last_name'     => 'Linger',
        ],
        [
            'user_login'    => 'hubert-hilborn',
            'user_nicename' => 'hubert-hilborn',
            'user_email'    => 'hubert.hilborn@hotmail.com',
            'user_pass'     => 'develoop3r',
            'role'          => ['member','develooper'],
            'display_name'  => 'Hubert-Hilborn',
            'first_name'    => 'Hubert',
            'last_name'     => 'Hilborn',
        ],
        [
            'user_login'    => 'hanna-mustonen',
            'user_nicename' => 'hanna-mustonen',
            'user_email'    => 'mustonenhanna@icloud.com',
            'user_pass'     => 'develoop3r',
            'role'          => ['member','develooper'],
            'display_name'  => 'Hanna-Mustonen',
            'first_name'    => 'Hanna',
            'last_name'     => 'Mustonen',
        ],
        [
            'user_login'    => 'develooper-5',
            'user_nicename' => 'develooper-5',
            'user_email'    => 'develooper-5@loopis.app',
            'user_pass'     => 'develoop3r',
            'role'          => ['member','develooper'],
            'display_name'  => 'develooper-5',
            'first_name'    => '',
            'last_name'     => '',
        ],
    ];
    
    // Loop through and create users if they do not exist
    foreach ($dev_users as $user){

        // Check if the user already exists
        if (username_exists($user['user_login'])) {
            continue;
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
        // Set role
        $user_id = new WP_User($user_id);
        foreach($user['role'] as $role){
            $user_id->set_role($role);
        }
    }
    loopis_elog_function_end_success('develooper_insert');
}   