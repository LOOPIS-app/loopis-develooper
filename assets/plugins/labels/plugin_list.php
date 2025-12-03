<?php

function plugin_list() {
    return [
        [
            'slug' => 'wp-debugging',
            'main' => 'wp-debugging/wp-debugging.php',
        ],
        [
            'slug'=> 'query-monitor',
            'main'=> 'query-monitor/query-monitor.php',
        ],
        [
            'slug'=> 'wordpress-reset',
            'main'=> 'wordpress-reset/wordpress-reset.php',
        ],
    ];
}