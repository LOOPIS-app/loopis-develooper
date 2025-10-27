<?php
/**
 * Sample collections for sample users and sample posts.
 * 
 */

function get_sample_users() {
    return [
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
            'display_name'  => 'Monica-Manager',
            'first_name'    => 'Monica',
            'last_name'     => 'Manager',
        ],
    ];
}

function get_sample_posts() {
    return [
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
            'post_content' => 'Hopfällbar kartong klädd med mörkgrå yta, förutom i botten. Lite skavanker men hel och ren.

30x30x30 cm.',
            'post_name' => 'papplada',  
            'feature_image' => 'post-10',          
        ],
        [
            'post_author' => 'gabby-giver',
            'post_date' => '2025-10-22 00:01:00',
            'post_title' => 'Pennfodral',
            'post_content' => 'Praktiskt och ihopfällbart med dragkedja.
Plats för 40 pennor 2 sudd – men de på bilden ingår ej. 🙂',
            'post_name' => 'pennfodral', 
            'feature_image' => 'post-11',           
        ],
        [
            'post_author' => 'gabby-giver',
            'post_date' => '2025-10-22 00:02:00',
            'post_title' => 'Tuschpennor',
            'post_content' => '18 stycken av ganska fin kvalité.

Det finns en tjock och en tunn spets på varje penna. De tunna har torkat men de tjocka funkar fint!',
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
}