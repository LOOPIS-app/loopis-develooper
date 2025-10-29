<?php
/**
 * Function to create LOOPIS sample posts in the WordPress database.
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

// Include WP functions
if ( ! function_exists( 'post_exists' ) ) {
    require_once ABSPATH . 'wp-admin/includes/post.php';
}
if ( ! function_exists('get_user_by') ) {
    require_once ABSPATH . 'wp-includes/pluggable.php';
}

require_once LOOPIS_DEVELOOPER_DIR . 'functions/labels/sample-posts.php';

/**
 * Insert posts into wp_posts
 * 
 * @return void
 */
function develooper_sample_posts_insert() {

    loopis_elog_function_start('develooper_sample_posts_insert');

    // Ensure WordPress rewrite rules are initialized
    global $wp_rewrite;
    if (!$wp_rewrite) {
        $wp_rewrite = new WP_Rewrite();
    }

    // Fetch sample posts from sample-posts.php
    $sample_posts = get_sample_posts();

    $current_time = new DateTime(current_time('mysql'));

    foreach($sample_posts as $post) {

        // 1. If post is already exist, skip it.
        if (post_exists($post['post_title'], $post['post_content'])) {
            loopis_elog_first_level('Post already exists: ' . $post['post_title']);
            continue;
        }

        // 2. Fetch post author.
        $user_id = get_user_by('login', $post['post_author']);

        // 3. Check if the user exists, if not, skip.
        if (!$user_id) {
            loopis_elog_first_level('User does not exist: ' . $post['post_author']);
            continue; 
        }

        $post_date = clone $current_time; 
        if ($post['post_date'] != '') {
            $post_date->modify($post['post_date']);
        }
        list($hour, $minute, $second) = explode(':', $post['post_time']);

        $post_date->setTime((int)$hour, (int)$minute, (int)$second);
        // 4. Insert post.
        $post_id = wp_insert_post([
            'post_author' => $user_id->ID,
            'post_date' => $post_date->format('Y-m-d H:i:s'),
            'post_title' => $post['post_title'],
            'post_content' => $post['post_content'],
            'post_name' => $post['post_name'],
            'comment_status' => 'open',
            'ping_status' => 'closed',
            'post_type' => 'post',
            'post_status' => 'publish'
        ]);

        // 5. If an error occurs in post insertion, throw error_log and skip.
        if (is_wp_error($post_id)) {
            loopis_elog_first_level('Failed to create post "' . $post['post_title'] . '": ' . $post_id->get_error_message());
            continue;
        } else {
            loopis_elog_first_level('Successfully created post "' . $post['post_title'] . '" (ID: ' . $post_id . ')');
        }

        // Set taxonomies (post tags and categories)
        wp_set_post_tags($post_id, $post['post_tags'], false);
        develooper_insert_sample_posts_category($post_id, $post['post_categories']);

        // 6. Retrieve local image file.
        $img_path = LOOPIS_DEVELOOPER_DIR . "assets/img/sample_posts/{$post['feature_image']}.jpg";

        // 7. Check if the file exists
        if (file_exists($img_path)) { 
            loopis_elog_first_level('Found image file: ' . basename($img_path));
            
            // 7.1. If yes, add image to the post.
            $attached_img = develooper_add_image_to_inserted_post($post_id, $img_path);

            if (is_wp_error($attached_img)) {
                loopis_elog_first_level('Failed to attach image to post "' . $post['post_title'] . '": ' . $attached_img->get_error_message());
            } else {
                // Set the featured image
                set_post_thumbnail($post_id, $attached_img);
                loopis_elog_first_level('Successfully set featured image for post "' . $post['post_title'] . '" (Attachment ID: ' . $attached_img . ')');
            }

        } else {
            // 7.2. Otherwise, throw error_log.
            loopis_elog_first_level('Image file does not exist: ' . $img_path);
        }
    }

    // Flush rewrite rules after inserting posts
    flush_rewrite_rules(false);

    loopis_elog_function_end_success('develooper_sample_posts_insert');
}

/**
 * Function to add image to the inserted post
 * 
 * @param int $post_id
 * @param string $image_path Local file path to the image
 * @return int|WP_Error Attachment ID on success, WP_Error on failure
 */
function develooper_add_image_to_inserted_post($post_id, $image_path) {
    
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';

    if (!file_exists($image_path)) {
        return new WP_Error('file_not_found', 'Image file does not exist: ' . $image_path);
    }

    // Temporarily disable organized uploads (no year/month folders)
    add_filter('upload_dir', function($upload_dir) {
        $upload_dir['subdir'] = '';
        $upload_dir['path'] = $upload_dir['basedir'];
        $upload_dir['url'] = $upload_dir['baseurl'];
        return $upload_dir;
    });

    // Get upload directory (with filter applied)
    $upload_dir = wp_upload_dir();
    
    // Remove the filter immediately after getting the directory
    remove_all_filters('upload_dir');
    
    // Generate unique filename
    $filename = wp_unique_filename($upload_dir['path'], basename($image_path));
    $new_file_path = $upload_dir['path'] . '/' . $filename;

    // Copy to uploads
    if (!copy($image_path, $new_file_path)) {
        return new WP_Error('copy_failed', 'Failed to copy to uploads: ' . $new_file_path);
    }

    // Get mime type
    $filetype = wp_check_filetype($filename, null);

    // Insert attachment
    $attachment_id = wp_insert_attachment(array(
        'post_mime_type' => $filetype['type'],
        'post_title'     => preg_replace('/\.[^.]+$/', '', $filename),
        'post_content'   => '',
        'post_status'    => 'inherit'
    ), $new_file_path, $post_id);

    if (is_wp_error($attachment_id)) {
        @unlink($new_file_path);
        return $attachment_id;
    }

    // Generate metadata
    $attach_data = wp_generate_attachment_metadata($attachment_id, $new_file_path);
    wp_update_attachment_metadata($attachment_id, $attach_data);

    return $attachment_id;
}


function develooper_insert_sample_posts_category($post_id, $categories) {

    foreach ($categories as $category) {
        
        $category_term = get_term_by( 'slug', $category, 'category' );

        if ( ! is_wp_error( $category_term ) && term_exists( $category_term->term_id, 'category' ) ) {
            wp_set_post_categories( $post_id, [ $category_term->term_id ], false );
        } else {
            loopis_elog_first_level('Category does not exist: ' . $category);
        }
    }
}