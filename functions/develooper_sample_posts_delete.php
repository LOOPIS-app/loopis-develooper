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

/**
 * Delete sample posts from wp_posts
 * 
 * @return void
 */
function develooper_sample_posts_delete() {

    loopis_elog_function_start('develooper_sample_posts_delete');
    $sample_posts_info = get_sample_posts();
    
    global $wpdb;
    // Get posts
    foreach ($sample_posts_info as $sample_post) {
        $post_name = $sample_post['post_name'];
        $post = get_page_by_path($post_name, OBJECT, 'post');
        if ($post) {
            wp_delete_post($post->ID, true);
            error_log("Deleted post: " . $post_name);
        } else {
            error_log("Post not found: " . $post_name);
        }
    }

    // Resets post count
    $wpdb->query("ALTER TABLE {$wpdb->posts} AUTO_INCREMENT = 1");
    loopis_elog_function_end_success('develooper_sample_posts_delete');
}
