<?php
/**
 * Inserts sample users into the WordPress database.
 * References from LOOPIS_Config plugin.
 * Referenced file name: loopis_user_insert.php
 * Credits: Johan Linger, Hubert Hilton and Johan Hagvil.
 * 
 * @package LOOPIS_Develooper
 * @subpackage Dev-tools
 */

require_once LOOPIS_DEV_DIR . 'functions/develooper_set_roles.php'; // Include user insert function

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Inserts users into wp_users
 * 
 * @return void
 */
function develooper_user_insert() {

    loopis_user_roles_set(); // Ensure roles are set up
    // Access WordPress database object
    global $wpdb;

    $inserted_users = []; // Array to hold details of inserted users
    
    $base_user = [
        [
            'user_login'    => 'gabby-giver',
            'user_nicename' => 'gabby-giver',
            'user_email'    => 'gabby-giver@loopis.app',
            'user_pass'     => 'memb3r',
            'role'          => ['member'],
            'display_name'  => 'Gabby-Giver',
            'first_name'    => 'Gabby',
            'last_name'     => 'Giver',
        ],
        [
            'user_login'    => 'fred-fetcher',
            'user_nicename' => 'fred-fetcher',
            'user_email'    => 'fred-fetcher@loopis.app',
            'user_pass'     => 'memb3r',
            'role'          => ['member'],
            'display_name'  => 'Fred-Fetcher',
            'first_name'    => 'Fred',
            'last_name'     => 'Fetcher',
        ],
        [
            'user_login'    => 'rebecca-raffle',
            'user_nicename' => 'rebecca-raffle',
            'user_email'    => 'rebecca-raffle@loopis.app',
            'user_pass'     => 'memb3r',
            'role'          => ['member'],
            'display_name'  => 'Rebecca-Raffle',
            'first_name'    => 'Rebecca',
            'last_name'     => 'Raffle',
        ],
        [
            'user_login'    => 'jessica-joiner',
            'user_nicename' => 'jessica-joiner',
            'user_email'    => 'jessica-joiner@loopis.app',
            'user_pass'     => 'memb3r',
            'role'          => ['member'],
            'display_name'  => 'Jessica-Joiner',
            'first_name'    => 'Jessica',
            'last_name'     => 'Joiner',
        ],
        [
            'user_login'    => 'monica-manager',
            'user_nicename' => 'monica-manager',
            'user_email'    => 'monica-manager@loopis.app',
            'user_pass'     => 'manag3r',
            'role'          => ['manager'],
            'display_name'  => 'MonicaManager',
            'first_name'    => 'Monica',
            'last_name'     => 'Manager',
        ],
    ];

    foreach ($base_user as $user) {

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

        /*if (is_wp_error($user_id)) {
            loopis_elog_first_level('Failed to create user ' . $user['user_login'] . ': ' . $user_id->get_error_message());
            continue;
        }*/

        // Add admin capabilities
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
            error_log('Failed to assign role to user ' . $user['user_login'] . ': ' . $user_id->get_error_message());
        }
    }

    return $inserted_users; // Return details of inserted users
}