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

    // Define sample posts
    $sample_posts = [
        [
            'post_author' => 'gabby-giver',
            'post_date' => '2023-10-22 17:00:00',
            'post_title' => 'Känguru!',
            'post_content' => 'Klassiskt plastdjur, made in China. 14 cm hög och 20 cm från nos- till svanstipp. Lite skavd på nosen men annars i gott skick. Ungen kan plockas ut ur pungen!',
            'post_name' => 'kanguru',
            'feature_image' => 'post-1',            
        ],
        [
            'post_author' => 'fred-fetcher',
            'post_date' => '2024-10-22 17:00:00',
            'post_title' => 'Trästol',
            'post_content' => 'Rejäl stol med hög rygg. Vadderad sits med fejkskinn. Bra skick men lite slitage, mest synligt på hörnet av sitsen, bild 2.',
            'post_name' => 'trastol',
            'feature_image' => 'post-2',            
        ],
        [
            'post_author' => 'gabby-giver',
            'post_date' => '2025-09-22 17:00:00',
            'post_title' => 'Rivjärn tupperware',
            'post_content' => 'Rivjärn på en behållare med handtag. Rymmer ca 600ml.',
            'post_name' => 'rivjarn_tupperware',
            'feature_image' => 'post-3',            
        ],
        [
            'post_author' => 'gabby-giver',
            'post_date' => '2025-10-15 17:00:00',
            'post_title' => 'Myggspray',
            'post_content' => 'Sprayflaska. Typ 90% full. Locket saknas tyvärr 🫤',
            'post_name' => 'myggspray',
            'feature_image' => 'post-4',            
        ],
        [
            'post_author' => 'rebecca-raffle',
            'post_date' => '2025-10-20 12:01:00',
            'post_title' => '📒 Liftarens guide till galaxen',
            'post_content' => 'Rensar i bokhyllan. Lite tjock och sliten pocket tryckt 1992.',
            'post_name' => 'loftarens_guide_till_galaxen',
            'feature_image' => 'post-5',            
        ],
        [
            'post_author' => 'rebecca-raffle',
            'post_date' => '2025-10-20 12:02:00',
            'post_title' => '"Finns det liv på Mars?"',
            'post_content' => 'Pocket från 2006 av Inger Edelfeldt. Om en ensamstående mamma med kärlek till David Bowie. Jag blev inte förtjust.',
            'post_name' => 'finns_det_liv_pa_mars',
            'feature_image' => 'post-6',           
        ],
        [
            'post_author' => 'rebecca-raffle',
            'post_date' => '2025-10-21 14:00:00',
            'post_title' => 'Knausgårds kamp...',
            'post_content' => 'Lång bok! Läste början och slutet bara för att förstå vad hans kamp handlar om… 😊',
            'post_name' => 'knausgards_kamp',
            'feature_image' => 'post-7',            
        ],
        [
            'post_author' => 'gabby-giver',
            'post_date' => '2025-10-21 14:01:00',
            'post_title' => 'Klösbräda & kattmynta',
            'post_content' => 'Har blivit liggandes hemma. Får se om kattmyntan har någon effekt fortfarande. 😊',
            'post_name' => 'klosbrada_kattmynta',
            'feature_image' => 'post-8',            
        ],
        [
            'post_author' => 'fred-fetcher',
            'post_date' => '2025-10-21 14:02:00',
            'post_title' => 'Rejäl kontorsstol',
            'post_content' => 'Okänt märke men känns som en äldre modell av någon ergonomisk klassiker. Den har fyra reglage som kan ta lite tid att förstå men alla verkar fungera. Den cykelventil som sitter på baksidan verkar dock inte ha någon effekt. Vikt cirka 20 kg.',
            'post_name' => 'rejal_kontorsstol',
            'feature_image' => 'post-9',            
        ],
        [
            'post_author' => 'gabby-giver',
            'post_date' => '2025-10-21 14:03:00',
            'post_title' => 'Papplåda',
            'post_content' => 'Hopfällbar kartong klädd med mörkgrå yta, förutom i botten. Lite skavanker men hel och ren. 30x30x30 cm.',
            'post_name' => 'papplada',  
            'feature_image' => 'post-10',          
        ],
        [
            'post_author' => 'gabby-giver',
            'post_date' => '2025-10-22 00:01:00',
            'post_title' => 'Pennfodral',
            'post_content' => 'Praktiskt och ihopfällbart med dragkedja. Plats för 40 pennor 2 sudd – men de på bilden ingår ej. 🙂',
            'post_name' => 'pennfodral', 
            'feature_image' => 'post-11',           
        ],
        [
            'post_author' => 'gabby-giver',
            'post_date' => '2025-10-22 00:02:00',
            'post_title' => 'Tuschpennor',
            'post_content' => '18 stycken av ganska fin kvalité. Det finns en tjock och en tunn spets på varje penna. De tunna har torkat men de tjocka funkar fint!',
            'post_name' => 'tuschpennor',
            'feature_image' => 'post-12',            
        ],
        [
            'post_author' => 'gabby-giver',
            'post_date' => '2025-10-22 00:03:00',
            'post_title' => 'Vattenkanna',
            'post_content' => '10 liter i klassisk modell',
            'post_name' => 'vattenkanna',
            'feature_image' => 'post-13',            
        ],
    ];

    foreach($sample_posts as $post) {

        // 1. If post is already exist, skip it.
        if (post_exists($post['post_title'], $post['post_content'], $post['post_date'])) {
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

        // 4. Insert post.
        $post_id = wp_insert_post([
            'post_author' => $user_id->ID,
            'post_date' => $post['post_date'],
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
    
<<<<<<< Updated upstream

        // Include required files for handling uploads
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    
    // Create a temp copy in PHP's temp dir
    $tmp_dir = sys_get_temp_dir();
    $tmp_name = wp_unique_filename( $tmp_dir, wp_basename( $image_url ) );
    $tmp_path = trailingslashit( $tmp_dir ) . $tmp_name;
=======
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';

    if (!file_exists($image_path)) {
        return new WP_Error('file_not_found', 'Image file does not exist: ' . $image_path);
    }
>>>>>>> Stashed changes

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
<<<<<<< Updated upstream
        return new WP_Error( 'upload_failed', "Error uploading image: " . $attachment_id->get_error_message() );
    } else {
=======
        @unlink($new_file_path);
>>>>>>> Stashed changes
        return $attachment_id;
    }

    // Generate metadata
    $attach_data = wp_generate_attachment_metadata($attachment_id, $new_file_path);
    wp_update_attachment_metadata($attachment_id, $attach_data);

    return $attachment_id;
}