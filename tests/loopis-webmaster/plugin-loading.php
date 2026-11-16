<?php
/**
 * Run from loopis-develooper with: php tests/loopis-webmaster/plugin-loading.php frontend|unauthorized|site-admin|admin
 */

define('ABSPATH', __DIR__ . '/');
$context = $argv[1] ?? '';
if (!in_array($context, array('frontend', 'unauthorized', 'site-admin', 'admin'), true)) {
    throw new RuntimeException('Expected frontend, unauthorized, site-admin, or admin context.');
}

$GLOBALS['webmaster_test_hooks'] = array();
function is_admin() { return $GLOBALS['context'] !== 'frontend'; }
function current_user_can($capability) { return $capability === 'manage_options' && in_array($GLOBALS['context'], array('site-admin', 'admin'), true); }
function is_super_admin() { return $GLOBALS['context'] === 'admin'; }
function add_action($hook, $callback, $priority = 10) { $GLOBALS['webmaster_test_hooks'][$hook][] = array('callback' => $callback, 'priority' => $priority); }
function plugins_url($path, $plugin) { return 'https://example.test/plugins/loopis-webmaster/' . $path; }
function add_menu_page(...$args) { $GLOBALS['webmaster_test_menu'] = $args; }
function add_submenu_page(...$args) { $GLOBALS['webmaster_test_submenus'][] = $args; }
function remove_submenu_page(...$args) { $GLOBALS['webmaster_test_removed_submenu'] = $args; }
function wp_parse_args($args, $defaults) { return array_merge($defaults, (array) $args); }
$wpdb = (object) array('base_prefix' => 'wp_');

require dirname(__DIR__, 3) . '/loopis-webmaster/loopis-webmaster.php';
foreach ($GLOBALS['webmaster_test_hooks']['init'] ?? array() as $hook) {
    call_user_func($hook['callback']);
}

$loaded = function_exists('loopis_image_migration_scan') && function_exists('loopis_image_migration_render_page')
    && function_exists('move_user') && function_exists('loopis_move_user_page') && function_exists('loopis_handle_move_user')
    && function_exists('loopis_qr_codes_render_stats') && class_exists('table_menu');
if ($loaded !== ($context === 'admin')) {
    throw new RuntimeException('Tool loading did not match the admin capability.');
}
foreach (array('admin_init', 'admin_menu', 'admin_post_loopis_image_migration_scan', 'admin_post_loopis_image_migration_process', 'wp_ajax_loopis_image_migration_process_ajax', 'admin_post_loopis_move_user') as $hook) {
    if (isset($GLOBALS['webmaster_test_hooks'][$hook]) !== ($context === 'admin')) {
        throw new RuntimeException('Unexpected registration for ' . $hook);
    }
}
if ($context === 'admin') {
    $menu_hooks = $GLOBALS['webmaster_test_hooks']['admin_menu'];
    usort($menu_hooks, function ($left, $right) { return $left['priority'] <=> $right['priority']; });
    foreach ($menu_hooks as $hook) {
        call_user_func($hook['callback']);
    }
    $menu = $GLOBALS['webmaster_test_menu'];
    $submenus = $GLOBALS['webmaster_test_submenus'];
    $submenu = $submenus[0];
    if ($menu[1] !== 'Webmaster' || $menu[2] !== 'manage_options' || $menu[3] !== 'loopis-webmaster'
        || $menu[4] !== '' || $menu[5] !== 'https://example.test/plugins/loopis-webmaster/assets/loopis-icon.png'
        || !is_file(dirname(__DIR__, 3) . '/loopis-webmaster/assets/loopis-icon.png')
        || $submenu[0] !== 'loopis-webmaster' || $submenu[2] !== 'Image Migration'
        || $submenu[3] !== 'manage_options' || $submenu[4] !== 'loopis-image-migration'
        || $submenu[5] !== 'loopis_image_migration_render_page'
        || $GLOBALS['webmaster_test_removed_submenu'] !== array('loopis-webmaster', 'loopis-webmaster')) {
        throw new RuntimeException('Webmaster menu configuration is incorrect.');
    }
    if (count($submenus) !== 3 || $submenus[1][0] !== 'loopis-webmaster' || $submenus[1][2] !== 'Move User'
        || $submenus[1][3] !== 'manage_options' || $submenus[1][4] !== 'move-user' || $submenus[1][5] !== 'loopis_move_user_page'
        || $submenus[2][0] !== 'loopis-webmaster' || $submenus[2][2] !== 'QR codes'
        || $submenus[2][3] !== 'manage_options' || $submenus[2][4] !== 'loopis-qrs'
        || !isset($GLOBALS['webmaster_test_hooks']['loopis_qr_codes_after_page'])
        || $GLOBALS['webmaster_test_hooks']['loopis_qr_codes_after_page'][0]['callback'] !== 'loopis_qr_codes_render_stats'
        || function_exists('loopis_webmaster_render_debug_log') || function_exists('loopis_webmaster_render_error_log')) {
        throw new RuntimeException('Webmaster tool submenu configuration is incorrect.');
    }
}
echo "Plugin loading test passed: $context.\n";
