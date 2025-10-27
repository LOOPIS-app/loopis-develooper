<?php
/**
 * Function to delete LOOPIS sample posts in the WordPress database.
 * 
 * This file is included from the WP admin page with the same name.
 * 
 * @package LOOPIS_Develooper
 * @subpackage Dev-tools
 */

if ( ! defined('ABSPATH')) {
    exit;
}

require_once LOOPIS_DEV_DIR .'functions/sample.php';

function develooper_sample_posts_delete() {

    loopis_elog_function_start('develooper_sample_posts_delete');
    $sample_posts_info = get_sample_posts();
    $posts = get_posts(['numberposts' => -1]);

    error_log("Length: " . count($posts));

    
    global $wpdb;
    // Get posts
    foreach ($posts as $post) {
      // Delete all posts
      wp_delete_post($post->ID, true);
    }

    // Resets post count
    $wpdb->query("ALTER TABLE {$wpdb->posts} AUTO_INCREMENT = 1");
    loopis_elog_function_end_success('develooper_sample_posts_delete');
}

/**
 * global $wpdb; Get all 
 * users except user 1(admin)
   * $users = get_users(['exclude' => [1]]);
    *foreach ($users as $user) {
     *   // Delete each user
      *  wp_delete_user($user->ID);
    *}
    *  //Resets user count
    *$wpdb->query("ALTER TABLE {$wpdb->users} AUTO_INCREMENT = 1");
 */
