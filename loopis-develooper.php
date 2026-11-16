<?php
/**
 * Plugin Name: LOOPIS Develooper
 * Plugin URI:  https://github.com/LOOPIS-app/loopis-develooper
 * Description: Plugin providing tools for developing LOOPIS.app
 * Version:     0.52
 * Author:      The Develoopers
 * Author URI:  https://loopis.org
 * License:     GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: loopis-develooper
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Run only in admin area
if (!is_admin()) {
    return;
}

// Check the current user's capability after WordPress has initialized.
function loopis_develooper_init()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    require_once __DIR__ . '/includes/samples/functions.php';
    require_once __DIR__ . '/includes/samples/page.php';
    require_once __DIR__ . '/includes/roles/functions.php';
    require_once __DIR__ . '/includes/roles/page.php';
    if (is_super_admin()) {
        require_once __DIR__ . '/includes/logs/functions.php';
        require_once __DIR__ . '/includes/logs/page.php';
        require_once __DIR__ . '/includes/logs/widget.php';
        add_action('wp_dashboard_setup', 'loopis_develooper_register_debug_dashboard_widget');
    }
    add_action('admin_menu', 'develooper_admin_menu');
}
add_action('init', 'loopis_develooper_init');

/** Register the Develooper menu and its tools. */
function develooper_admin_menu()
{
    add_menu_page(
        'LOOPIS Develooper',
        'Develooper',
        'manage_options',
        'loopis_dev_main',
        '',
        plugins_url('assets/loopis-icon.png', __FILE__)
    );
    add_submenu_page('loopis_dev_main', 'Samples', 'Samples', 'manage_options', 'develooper_samples', 'develooper_samples_page');
    add_submenu_page('loopis_dev_main', 'Roles', 'Roles', 'manage_options', 'develooper_roles', 'develooper_roles_page');
    if (is_super_admin()) {
        add_submenu_page('loopis_dev_main', 'debug.log', 'debug.log', 'manage_options', 'loopis-debug-log', 'loopis_develooper_render_debug_log');
        add_submenu_page('loopis_dev_main', 'error_log', 'error_log', 'manage_options', 'loopis-error-log', 'loopis_develooper_render_error_log');
    }
    remove_submenu_page('loopis_dev_main', 'loopis_dev_main');
}