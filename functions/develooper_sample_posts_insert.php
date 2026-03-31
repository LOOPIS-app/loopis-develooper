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
if (!function_exists('post_exists')) {
    require_once ABSPATH . 'wp-admin/includes/post.php';
}
if (!function_exists('get_user_by')) {
    require_once ABSPATH . 'wp-includes/pluggable.php';
}

// Import sample-post lists
require_once LOOPIS_DEVELOOPER_DIR . 'assets/samples/posts.php';

/**
 * Insert posts into wp_posts
 * 
 * @return void
 */
function develooper_sample_posts_insert()
{

    loopis_elog_function_start('develooper_sample_posts_insert');

    // Ensure WordPress rewrite rules are initialized
    global $wp_rewrite;
    if (!$wp_rewrite) {
        $wp_rewrite = new WP_Rewrite();
    }

    // Wordpress WP_Query to prevent running too early
    global $wp_query;
    if (!$wp_query) {
        $wp_query = new WP_Query();
    }

    // Fetch sample posts from sample-posts.php
    $sample_posts = get_sample_posts();

    // Current time for post date calculations
    $current_time = new DateTime(current_time('mysql'));

    foreach ($sample_posts as $post) {

        // 1. Fetch existing post by slug.
        $post_name = $post['post_name'];
        $existed_post = get_page_by_path($post_name, OBJECT, 'post');
        // If post is already exist, skip it.
        if ($existed_post) {
            loopis_elog_first_level('Post already exists: ' . $post['post_title'] . ' (Slug: ' . $post['post_name'] . ')');
            continue;
        }

        // 2. Fetch post author.
        $user_id = get_user_by('login', $post['post_author']);

        // 3. Check if the user exists, if not, skip.
        if (!$user_id) {
            loopis_elog_first_level('User does not exist: ' . $post['post_author']);
            continue;
        }

        // Prepare post date and time
        $post_date = clone $current_time;
        // if post_date is specified, modify it, else use current date
        if ($post['post_date'] != '') {
            $post_date->modify($post['post_date']);
        }
        // divide post_time value into hour, minute, second from hh:mm:ss.
        list($hour, $minute, $second) = explode(':', $post['post_time']);

        // set time
        $post_date->setTime((int) $hour, (int) $minute, (int) $second);
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
            // report success
            loopis_elog_first_level('Successfully created post "' . $post['post_title'] . '" (ID: ' . $post_id . ')');
        }

        // Set taxonomies (post tags and categories)
        wp_set_post_tags($post_id, $post['post_tags'], false);
        develooper_insert_sample_posts_category($post_id, $post['post_categories']);

        // 6. Retrieve local image file.
        $img_path = LOOPIS_DEVELOOPER_DIR . "assets/samples/img/{$post['feature_image']}.jpg";

        // 7. Check if the file exists
        if (file_exists($img_path)) {
            loopis_elog_first_level('Found image file: ' . basename($img_path));

            // 7.1. If yes, add image to the post.
            $attached_img = develooper_add_image_to_inserted_post($post_id, $img_path);

            // if $attached_img is WP_Error, show error log and skip adding featured image
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

        // 8. Add post meta
        $add_meta_result = develooper_postmeta_insert($post_id, $post);
        if (is_wp_error($add_meta_result)) {
            loopis_elog_first_level('Failed to add post meta for post "' . $post['post_title'] . '": ' . $add_meta_result->get_error_message());
        } else {
            loopis_elog_first_level('Successfully added post meta for post "' . $post['post_title'] . '"');
        }
    }

    // Flush rewrite rules after inserting posts
    flush_rewrite_rules(false);

    // final log
    loopis_elog_function_end_success('develooper_sample_posts_insert');
}

/**
 * Function to add image to the inserted post
 * 
 * @param int $post_id
 * @param string $image_path Local file path to the image
 * @return int|WP_Error Attachment ID on success, WP_Error on failure
 */
function develooper_add_image_to_inserted_post($post_id, $image_path)
{

    // Include required WP files for handling media
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';

    // Check if file exists
    if (!file_exists($image_path)) {
        return new WP_Error('file_not_found', 'Image file does not exist: ' . $image_path);
    }

    // Temporarily disable organized uploads (no year/month folders)
    add_filter('upload_dir', function ($upload_dir) {
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
        'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
        'post_content' => '',
        'post_status' => 'inherit'
    ), $new_file_path, $post_id);

    //unlink on error
    if (is_wp_error($attachment_id)) {
        @unlink($new_file_path);
        return $attachment_id;
    }

    // Generate metadata
    $attach_data = wp_generate_attachment_metadata($attachment_id, $new_file_path);
    wp_update_attachment_metadata($attachment_id, $attach_data);

    return $attachment_id;
}

/**
 * Function to assign categories to the inserted post
 * 
 * @param int $post_id
 * @param array $categories Array of category slugs
 * @return void
 */
function develooper_insert_sample_posts_category($post_id, $categories)
{

    // Assign categories to the post
    foreach ($categories as $category) {

        // get category term by slug
        $category_term = get_term_by('slug', $category, 'category');

        // if term exists, assign to post
        if (!is_wp_error($category_term) && term_exists($category_term->term_id, 'category')) {
            wp_set_post_categories($post_id, [$category_term->term_id], false);
            // else report non-existence
        } else {
            loopis_elog_first_level('Category does not exist: ' . $category);
        }
    }
}

/**
 * Insert post meta for the inserted post
 *
 * @param int $post_id
 * @param array $post
 * @return void
 */
function develooper_postmeta_insert($post_id, $post)
{
    loopis_elog_function_start('develooper_postmeta_insert');
    if (!post_exists($post['post_title'])) {
        loopis_elog_first_level('This post does not exist, cannot add meta. Post Title: ' . $post['post_title']);
        return new WP_Error('post_not_found', 'Post does not exist: ' . $post['post_title']);
    }

    // loop through post meta and add to the post
    foreach ($post['post_meta'] as $meta_key => $meta_value) {

        // Handle datetime meta: 
        // if meta key ends with '_date' and value is an array with 'date' and 'time' keys, convert to datetime format.
        // If time value is empty, set meta value to empty string to skip adding meta.
        if (str_ends_with($meta_key, '_date') && is_array($meta_value) && isset($meta_value['date']) && isset($meta_value['time'])) {
            // If time is specified, convert to datetime format, else set null to skip adding specific value.
            $meta_value['time'] != '' ? $meta_value = sample_post_datetime_handler($meta_value) : $meta_value = '';
        }

        // Handle user-related meta:
        if ($meta_key == 'participants' || $meta_key == 'queue' && is_array($meta_value) && !empty($meta_value)) {
            // Convert participant logins to user IDs, filter out non-existing users
            $meta_value = fetch_user_by_login_array($meta_value, $meta_key);
        } else if ($meta_key == 'fetcher' && is_string($meta_value) && $meta_value != '') {
            // Convert fetcher value to user display name if user exists, else keep original value
            $meta_value = fetch_user_by_login($meta_value, $meta_key);
        }

        // Handle post-related meta:
        if (($meta_key == 'forward_post' || $meta_key == 'previous_post') && is_string($meta_value) && $meta_value != '') {
            // Get post by slug of forward_post and previous_post from meta value to convert post_name to id.
            $get_sample_post = get_page_by_path($meta_value, OBJECT, 'post');
            if ($get_sample_post) {
                $meta_value = $get_sample_post->ID;
                loopis_elog_first_level('Post title found: ' . $get_sample_post->post_title);
            } else {
                loopis_elog_first_level('Forward post does not exist: ' . $meta_value);
                $meta_value = '';
            }

            // if current meta key is previous_post, also add forward_post postmeta as id to the previous post with current post id as value to link the two posts.
            if ($meta_key == 'previous_post' && $get_sample_post) {
                update_post_meta($meta_value, 'forward_post', $post_id);
                loopis_elog_first_level('Updated forward_post meta for post ID: ' . $meta_value . ' with value: ' . $post_id);

            }
        }

        //update_post_meta will update postmeta if exist, add postmeta automatically if not exist. (To prevent duplication when run multiple times)
        update_post_meta($post_id, $meta_key, $meta_value);
        loopis_elog_first_level('Added post meta: ' . $meta_key . ' = ' . $meta_value);
    }

    loopis_elog_function_end_success('develooper_postmeta_insert');
}

/**
 * Fetch user IDs by logins, if users do not exist, log the info and return null for those users
 * 
 * @param array $login_array Array of user logins
 * @param string $key_context Context for logging (e.g. meta key name)
 * @return array Array of user IDs
 */

function fetch_user_by_login_array(array $login_array, string $key_context = '')
{
    // Array to hold user IDs
    $user_ids = [];
    // Loop through each login and fetch user ID
    foreach ($login_array as $login) {
        $user_id = fetch_user_by_login($login, $key_context);
        if ($user_id) {
            $user_ids[] = $user_id;
        }
    }
    return $user_ids;
}

/**
 * Fetch user ID by login, if user does not exist, log the info and return null
 * 
 * @param string $login User login
 * @param string $key_context Context for logging (e.g. meta key name)
 * @return int|null User ID if exists, null if not exists
 */

function fetch_user_by_login($login, string $key_context = '')
{
    $user = get_user_by('login', $login);
    if ($user) {
        return $user->ID;
    } else {
        loopis_elog_first_level('User login: ' . $login . ', does not found under ' . $key_context . '.');
        return null;
    }
}

/**
 * Handle datetime from seperate date and time fields, convert to 'Y-m-d H:i:s' format
 * 
 * @param array $datetime Array with 'date' and 'time' keys
 * @return string Datetime in 'Y-m-d H:i:s' format
 */

function sample_post_datetime_handler($datetime)
{
    $current_time = new DateTime(current_time('mysql'));

    $date = $datetime['date'];
    $time = $datetime['time'];

    // Prepare date and time
    $post_date = clone $current_time;
    // if date is specified, modify it, else use current date
    if ($date != '') {
        $post_date->modify($date);
    }
    // divide post_time value into hour, minute, second from hh:mm:ss.
    list($hour, $minute, $second) = explode(':', $time);

    // set time
    $post_date->setTime((int) $hour, (int) $minute, (int) $second);
    return $post_date->format('Y-m-d H:i:s');
}


/* datetime handling example for post meta (for future reference or revert change if needed)

$current_time = new DateTime(current_time('mysql'));
// Prepare post date and time
$post_date = clone $current_time;
// if post_date is specified, modify it, else use current date
if ($post['post_date'] != '') {
    $post_date->modify($post['post_date']);
}
// divide post_time value into hour, minute, second from hh:mm:ss.
list($hour, $minute, $second) = explode(':', $post['post_time']);

// set time
$post_date->setTime((int) $hour, (int) $minute, (int) $second);

*/