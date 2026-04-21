<?php
/**
 * Plugin Name: LOOPIS Develooper
 * Plugin URI:  https://github.com/LOOPIS-app/loopis-develooper
 * Description: Plugin providing tools for developing LOOPIS.app
 * Version:     0.51
 * Author:      The Develoopers
 * Author URI:  https://loopis.org
 * License:     GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: loopis-develooper
 */

/*
 * Copyright (C) 2026 LOOPIS
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Run only in admin area
if (!is_admin()) {
    return;
}

// Define plugin version
define('LOOPIS_DEVELOOPER_VERSION', '0.51');

// Define plugin folder path constants
define('LOOPIS_DEVELOOPER_DIR', plugin_dir_path(__FILE__));    // Server-side path to /wp-content/plugins/loopis-develooper/
define('LOOPIS_DEVELOOPER_URL', plugin_dir_url(__FILE__));     // Client-side path to https://site.com/wp-content/plugins/loopis-develooper/

// Enqueue temporary CSS
add_action('admin_enqueue_scripts', 'loopis_develooper_enqueue_assets');

function loopis_develooper_enqueue_assets()
{
    wp_enqueue_style(
        'loopis-develooper-styles',
        LOOPIS_DEVELOOPER_URL . 'assets/css/temporary.css',
        array(),
        filemtime(LOOPIS_DEVELOOPER_DIR . 'assets/css/temporary.css')
    );
    wp_enqueue_script(
        'loopis-develooper-scripts',
        LOOPIS_DEVELOOPER_URL . 'assets/scripts/loader.js',
        array('jquery'),
        filemtime(LOOPIS_DEVELOOPER_DIR . 'assets/scripts/loader.js')
    );
}

// Define folders to include
function develooper_load_files()
{
    develooper_include_folder('interface');
    develooper_include_folder('pages');
}

// Function to include all PHP files in a folder
function develooper_include_folder($folder_name)
{
    $absolute_path = LOOPIS_DEVELOOPER_DIR . '/' . $folder_name;
    if (is_dir($absolute_path)) {
        foreach (glob($absolute_path . '/*.php') as $file) {
            include_once $file;
        }
    } else {
        error_log("Failed to include folder: {$folder_name}");
    }
}

// Admin menu hook
add_action('admin_menu', 'develooper_admin_menu');

// Load files when all plugins are loaded
add_action('plugins_loaded', 'develooper_load_files');