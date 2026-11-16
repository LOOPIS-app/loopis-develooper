<?php
/** Run from loopis-develooper with: php tests/loopis-admin/plugin-loading.php frontend|mainsite|subsite */

$context = $argv[1] ?? '';
if (!in_array($context, array('frontend', 'mainsite', 'subsite'), true)) {
    throw new RuntimeException('Expected frontend, mainsite, or subsite.');
}

define('ABSPATH', __DIR__ . '/');
$GLOBALS['admin_test_hooks'] = array();
$GLOBALS['admin_test_styles'] = array();
$GLOBALS['admin_test_inline_styles'] = array();
$GLOBALS['admin_test_editor_styles'] = array();
$GLOBALS['admin_test_menus'] = array();
$GLOBALS['admin_test_submenus'] = array();

function is_admin() { return $GLOBALS['context'] !== 'frontend'; }
function is_main_site() { return $GLOBALS['context'] === 'mainsite'; }
function plugin_dir_path($file) { return dirname($file) . '/'; }
function plugin_dir_url($file) { return 'https://example.test/plugins/loopis-admin/'; }
function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
    $GLOBALS['admin_test_hooks'][$hook][] = array('callback' => $callback, 'priority' => $priority);
}
function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
    add_action($hook, $callback, $priority, $accepted_args);
}
function wp_enqueue_style(...$args) { $GLOBALS['admin_test_styles'][] = $args; }
function wp_add_inline_style(...$args) { $GLOBALS['admin_test_inline_styles'][] = $args; }
function add_editor_style($path) { $GLOBALS['admin_test_editor_styles'][] = $path; }
function taxonomy_exists($taxonomy) { return false; }
function post_type_exists($post_type) { return true; }
function get_taxonomy($taxonomy) { return false; }
function add_menu_page(...$args) { $GLOBALS['admin_test_menus'][] = $args; }
function add_submenu_page(...$args) { $GLOBALS['admin_test_submenus'][] = $args; }

function admin_test_assert($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$plugin_dir = dirname(__DIR__, 3) . '/loopis-admin';
require $plugin_dir . '/loopis-admin.php';

if ($context === 'frontend') {
    admin_test_assert($GLOBALS['admin_test_hooks'] === array(), 'Frontend requests must not register admin hooks.');
    admin_test_assert(!defined('LOOPIS_ADMIN_DIR') && !defined('LOOPIS_ADMIN_URL'), 'Frontend requests must not define admin paths.');
    echo "Admin plugin loading test passed: $context.\n";
    exit;
}

$hooks = $GLOBALS['admin_test_hooks']['plugins_loaded'] ?? array();
$expected_loader = $context === 'mainsite' ? 'loopis_admin_mainsite_files' : 'loopis_admin_subsite_files';
admin_test_assert(count($hooks) === 1 && $hooks[0]['callback'] === $expected_loader, 'Incorrect site-specific loader.');
call_user_func($hooks[0]['callback']);

$included = get_included_files();
foreach (array('admin-tweaks-id-columns.php', 'text-editor-tweaks.php', 'use-twemoji-admin.php') as $file) {
    admin_test_assert(in_array($plugin_dir . '/filters/' . $file, $included, true), 'Missing filter: ' . $file);
}
foreach (array('admin-menu.php', 'admin-menu-posts.php', 'admin-menu-cpt.php', 'admin-menu-reorder.php') as $file) {
    admin_test_assert(in_array($plugin_dir . '/menu/' . $context . '/' . $file, $included, true), 'Missing site menu file: ' . $file);
    $other = $context === 'mainsite' ? 'subsite' : 'mainsite';
    admin_test_assert(!in_array($plugin_dir . '/menu/' . $other . '/' . $file, $included, true), 'Loaded a menu file for the wrong site: ' . $file);
}

$hooks = $GLOBALS['admin_test_hooks']['admin_menu'] ?? array();
admin_test_assert(count($hooks) === 1 && $hooks[0]['callback'] === 'loopis_admin_menu' && $hooks[0]['priority'] === 999, 'Admin menu hook is incorrect.');
admin_test_assert(isset($GLOBALS['admin_test_hooks']['manage_posts_columns'], $GLOBALS['admin_test_hooks']['tiny_mce_before_init'], $GLOBALS['admin_test_hooks']['admin_head']), 'Common filter hooks are missing.');
admin_test_assert($GLOBALS['admin_test_editor_styles'] === array('assets/css/base.css'), 'Editor style was not registered.');

$hooks = $GLOBALS['admin_test_hooks']['admin_enqueue_scripts'] ?? array();
foreach ($hooks as $hook) {
    call_user_func($hook['callback']);
}
$styles = $GLOBALS['admin_test_styles'];
$inline_styles = $GLOBALS['admin_test_inline_styles'];
$stylesheet = $plugin_dir . '/assets/loopis-admin.css';
$icon = $context === 'mainsite' ? 'icon-area.png' : 'icon-gifts.png';
admin_test_assert(count($styles) === 1 && $styles[0] === array('loopis-admin-styles', 'https://example.test/plugins/loopis-admin/assets/loopis-admin.css', array(), filemtime($stylesheet)), 'Admin stylesheet was not enqueued correctly.');
admin_test_assert(count($inline_styles) === 1 && $inline_styles[0][0] === 'loopis-admin-styles' && strpos($inline_styles[0][1], $icon) !== false, 'Incorrect site-specific Posts icon.');

$menu = array(5 => array('Posts', 'edit_posts', 'edit.php'));
loopis_admin_menu();
$expected_title = $context === 'mainsite' ? 'Areas' : 'Gifts';
$expected_types = $context === 'mainsite' ? array('edit.php?post_type=faq', 'edit.php?post_type=news') : array('edit.php?post_type=news', 'edit.php?post_type=support');
admin_test_assert($menu[5][0] === $expected_title, 'Incorrect Posts menu label.');
admin_test_assert(array_column($GLOBALS['admin_test_menus'], 3) === $expected_types, 'Incorrect custom post type menus.');
admin_test_assert(count($GLOBALS['admin_test_submenus']) === 4, 'Incorrect custom post type submenus.');

echo "Admin plugin loading test passed: $context.\n";
