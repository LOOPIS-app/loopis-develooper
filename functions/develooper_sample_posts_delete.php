<?php
/**
 * Function to delete LOOPIS sample posts in the WordPress database.
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

// Include functions
require_once LOOPIS_DEVELOOPER_DIR .'assets/sample_posts/labels/sample-posts.php';

/**
 * Delete sample posts from wp_posts
 * 
 * @return void
 */
function develooper_sample_posts_delete() {

    // Start logging
    loopis_elog_function_start('develooper_sample_posts_delete');

    // Fetch sample posts from sample-posts.php
    $sample_posts_info = get_sample_posts();
    // Access to the database
    global $wpdb;
    
    // Get and delete posts with their attachments
    foreach ($sample_posts_info as $sample_post) {
        // Get post by slug
        $post_name = $sample_post['post_name'];
        // Fetch the post object
        $post = get_page_by_path($post_name, OBJECT, 'post');
        
        // If post exists, delete it along with its attachments
        if ($post) {
            // Get all attachments (images) for this post
            $attachments = get_attached_media('image', $post->ID);
            
            // Delete each attachment
            foreach ($attachments as $attachment) {
                wp_delete_attachment($attachment->ID, true); // true = force delete, bypass trash
                loopis_elog_first_level('Deleted attachment: ' . $attachment->post_title . ' (ID: ' . $attachment->ID . ')');
            }
            
            // Delete the post itself
            wp_delete_post($post->ID, true); // true = force delete, bypass trash
            loopis_elog_first_level('Deleted post: ' . $post_name . ' (ID: ' . $post->ID . ')');
        } else {
            //report non-existence
            loopis_elog_first_level('Post not found: ' . $post_name);
        }
    }

    // Reset post count
    $wpdb->query("ALTER TABLE {$wpdb->posts} AUTO_INCREMENT = 1");
    
    //final log
    loopis_elog_function_end_success('develooper_sample_posts_delete');
}