<?php
/**
 * Function to create LOOPIS sample posts in the WordPress database.
 * 
 * This file is included from the WP admin page with the same name.
 * 
 * @package LOOPIS_Develooper
 * @subpackage Dev-tools
 */

if (! defined('ABSPATH')) {
    exit;
}

if ( ! function_exists( 'post_exists' ) ) {
    require_once ABSPATH . 'wp-admin/includes/post.php';
}


/**
 * Insert posts into wp_posts
 * 
 * @return void
 */
function develooper_sample_posts_insert() {

    loopis_elog_function_start('develooper_sample_post_insert');

    global $wpdb;

    $inserted_posts = [];


    $sample_posts = [
        [
            'post_author' => 'gabby-giver', //4
            'post_date' => '2023-10-22 17:00:00',
            'post_title' => 'Känguru!',
            'post_content' => 'Klassiskt plastdjur, made in China. 14 cm hög och 20 cm från nos- till svanstipp. Lite skavd på nosen men annars i gott skick. Ungen kan plockas ut ur pungen!',
            'post_name' => 'kanguru',            
        ],
        [
            'post_author' => 'fred-fetcher',//5
            'post_date' => '2024-10-22 17:00:00',
            'post_title' => 'Trästol',
            'post_content' => 'Rejäl stol med hög rygg. Vadderad sits med fejkskinn. Bra skick men lite slitage, mest synligt på hörnet av sitsen, bild 2.',
            'post_name' => 'trastol',            
        ],
        [
            'post_author' => 'gabby-giver',//4
            'post_date' => '2025-09-22 17:00:00',
            'post_title' => 'Rivjärn tupperware',
            'post_content' => 'Rivjärn på en behållare med handtag. Rymmer ca 600ml.',
            'post_name' => 'rivjarn_tupperware',            
        ],
        [
            'post_author' => 'gabby-giver',//4
            'post_date' => '2025-10-15 17:00:00',
            'post_title' => 'Myggspray',
            'post_content' => 'Sprayflaska. Typ 90% full. Locket saknas tyvärr 🫤',
            'post_name' => 'myggspray',            
        ],
        [
            'post_author' => 'rebecca-raffle',//22
            'post_date' => '2025-10-20 12:01:00',
            'post_title' => '📒 Liftarens guide till galaxen',
            'post_content' => 'Rensar i bokhyllan. Lite tjock och sliten pocket tryckt 1992.',
            'post_name' => 'loftarens_guide_till_galaxen',            
        ],
        [
            'post_author' => 'rebecca-raffle',//22
            'post_date' => '2025-10-20 12:02:00',
            'post_title' => '"Finns det liv på Mars?"',
            'post_content' => 'Pocket från 2006 av Inger Edelfeldt. Om en ensamstående mamma med kärlek till David Bowie. Jag blev inte förtjust.',
            'post_name' => 'finns_det_liv_pa_mars',            
        ],
        [
            'post_author' => 'rebecca-raffle',//22
            'post_date' => '2025-10-21 14:00:00',
            'post_title' => 'Knausgårds kamp...',
            'post_content' => 'Lång bok! Läste början och slutet bara för att förstå vad hans kamp handlar om… 😊',
            'post_name' => 'knausgards_kamp',            
        ],
        [
            'post_author' => 'gabby-giver',//4
            'post_date' => '2025-10-21 14:01:00',
            'post_title' => 'Klösbräda & kattmynta',
            'post_content' => 'Har blivit liggandes hemma. Får se om kattmyntan har någon effekt fortfarande. 😊',
            'post_name' => 'klosbrada_kattmynta',            
        ],
        [
            'post_author' => 'fred-fetcher',//5
            'post_date' => '2025-10-21 14:02:00',
            'post_title' => 'Rejäl kontorsstol',
            'post_content' => 'Okänt märke men känns som en äldre modell av någon ergonomisk klassiker. Den har fyra reglage som kan ta lite tid att förstå men alla verkar fungera. Den cykelventil som sitter på baksidan verkar dock inte ha någon effekt. Vikt cirka 20 kg.',
            'post_name' => 'rejal_kontorsstol',            
        ],
        [
            'post_author' => 'gabby-giver',//4
            'post_date' => '2025-10-21 14:03:00',
            'post_title' => 'Papplåda',
            'post_content' => 'Hopfällbar kartong klädd med mörkgrå yta, förutom i botten. Lite skavanker men hel och ren.

30x30x30 cm.',
            'post_name' => 'papplada',            
        ],
        [
            'post_author' => 'gabby-giver',//4
            'post_date' => '2025-10-22 00:01:00',
            'post_title' => 'Pennfodral',
            'post_content' => 'Praktiskt och ihopfällbart med dragkedja.
Plats för 40 pennor 2 sudd – men de på bilden ingår ej. 🙂',
            'post_name' => 'pennfodral',            
        ],
        [
            'post_author' => 'gabby-giver',//4
            'post_date' => '2025-10-22 00:02:00',
            'post_title' => 'Tuschpennor',
            'post_content' => '18 stycken av ganska fin kvalité.

Det finns en tjock och en tunn spets på varje penna. De tunna har torkat men de tjocka funkar fint!',
            'post_name' => 'tuschpennor',            
        ],
        [
            'post_author' => 'gabby-giver',//4
            'post_date' => '2025-10-22 00:03:00',
            'post_title' => 'Vattenkanna',
            'post_content' => '10 liter i klassisk modell',
            'post_name' => 'vattenkanna',            
        ],
    ];

    $sample_post_images = [
        [
            'image_url' => 'C:\Users\First\VSCode\WP_plugin_dev\loopis-develooper\assets\img\sample_posts\post-1.jpg',
            'post_name'=> '',
        ],
    ];

    $post_num = 1;
    foreach($sample_posts as $post) {

        //1.1 if post is already exist, skip it.
        if (post_exists($post['post_title'], $post['post_content'], $post['post_date'])) {
            continue;
        };

        //1.2 insert post.
        $post_id = wp_insert_post([
            'post_author' => $post['post_author'],
            'post_date' => $post['post_date'],
            'post_title' => $post['post_title'],
            'post_content' => $post['post_content'],
            'post_name' => $post['post_name'],
            'comment_status' => 'open',
            'ping_status' => 'closed',
            'post_type' => 'post',
            'post_status' => 'publish'
        ]);

        $num_string = (string) $post_num;

        $img_path = plugin_dir_path( __FILE__ ) . "..\\assets\\img\\sample_posts\\post-{$num_string}.jpg";

        if (file_exists($img_path)) { 
            error_log('file exists');
            develooper_add_image_to_inserted_post($post_id, $img_path);
        } else {
            error_log("message: {$img_path} does not exist");
        }

        if (is_wp_error($post_id)) {
            loopis_elog_first_level('Failed to create post ' . $post['post_title'] . ': ' . $post_id->get_error_message());
            continue;
        }

        $post_num = $post_num + 1;
    }

    loopis_elog_function_end_success('develooper_sample_post_insert');
}

function develooper_add_image_to_inserted_post($post_id, $image_url) {
    

        // Include required files for handling uploads
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    // Upload and attach image to post
    $attachment_id = media_handle_sideload(
        array(
        'name'     => basename($image_url),
        'tmp_name' => $image_url
    ), $post_id);

    if (is_wp_error($attachment_id)) {
        echo "Error uploading image: " . $attachment_id->get_error_message();
    } else {
        // Set the featured image
        set_post_thumbnail($post_id, $attachment_id);
    }
}