<?php
/** Run from loopis-develooper with: php tests/loopis-develooper/plugin-loading.php frontend|unauthorized|site-admin|super-no-cap|admin */

$context = $argv[1] ?? '';
if (!in_array($context, array('frontend', 'unauthorized', 'site-admin', 'super-no-cap', 'admin'), true)) {
    throw new RuntimeException('Expected frontend, unauthorized, site-admin, super-no-cap, or admin.');
}

$fixture = sys_get_temp_dir() . '/loopis-develooper-test-' . bin2hex(random_bytes(8));
mkdir($fixture . '/wp-includes', 0700, true);
mkdir($fixture . '/wp-admin/includes', 0700, true);
file_put_contents($fixture . '/wp-includes/pluggable.php', "<?php\n");
foreach (array('post.php', 'user.php', 'ms.php') as $file) {
    file_put_contents($fixture . '/wp-admin/includes/' . $file, "<?php\n");
}
define('ABSPATH', $fixture . '/');

$GLOBALS['test_hooks'] = array();
$GLOBALS['test_submenus'] = array();
function is_admin() { return $GLOBALS['context'] !== 'frontend'; }
function current_user_can($capability) { return $capability === 'manage_options' && in_array($GLOBALS['context'], array('site-admin', 'admin'), true); }
function is_super_admin() { return in_array($GLOBALS['context'], array('super-no-cap', 'admin'), true); }
function plugin_dir_path($file) { return dirname($file) . '/'; }
function plugin_dir_url($file) { return 'https://example.test/loopis-develooper/'; }
function plugins_url($path, $file) { return 'https://example.test/loopis-develooper/' . $path; }
function esc_html($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function get_user_by($field, $value) { return false; }
function get_current_blog_id() { return 1; }
function is_user_member_of_blog($user_id, $blog_id) { return false; }
function add_action($hook, $callback) { $GLOBALS['test_hooks'][$hook][] = $callback; }
function add_menu_page(...$args) { $GLOBALS['test_menu'] = $args; }
function add_submenu_page(...$args) { $GLOBALS['test_submenus'][] = $args; }
function remove_submenu_page(...$args) {}
function wp_add_dashboard_widget(...$args) { $GLOBALS['test_dashboard_widgets'][] = $args; }

try {
    require dirname(__DIR__, 2) . '/loopis-develooper.php';
    foreach ($GLOBALS['test_hooks']['init'] ?? array() as $callback) {
        call_user_func($callback);
    }

    $authorized = in_array($context, array('site-admin', 'admin'), true);
    if (function_exists('develooper_insert') || is_file(dirname(__DIR__, 2) . '/functions/develooper_insert.php')) {
        throw new RuntimeException('Unused developer-user insertion must be removed.');
    }
    if (function_exists('develooper_page') || is_file(dirname(__DIR__, 2) . '/includes/develooper/page.php')) {
        throw new RuntimeException('Unused developer page must be removed.');
    }
    foreach (array('develooper_samples_page', 'develooper_roles_page', 'loopis_develooper_get_roles', 'develooper_sample_posts_insert', 'develooper_sample_posts_delete', 'develooper_users_insert', 'loopis_users_delete') as $function) {
        if (function_exists($function) !== $authorized) {
            throw new RuntimeException('Unexpected loading of ' . $function);
        }
        foreach (array('loopis_develooper_log_path', 'loopis_develooper_read_log', 'loopis_develooper_render_debug_log', 'loopis_develooper_render_error_log', 'loopis_develooper_register_debug_dashboard_widget', 'loopis_develooper_render_debug_dashboard_widget') as $function) {
            if (function_exists($function) !== ($context === 'admin')) {
                throw new RuntimeException('Unexpected log viewer loading: ' . $function);
            }
        }
        if (function_exists('develooper_load_files') || function_exists('develooper_include_folder')) {
            throw new RuntimeException('Automatic folder loading must not be used.');
        }
    }
    if (isset($GLOBALS['test_hooks']['admin_menu']) !== $authorized || isset($GLOBALS['test_hooks']['admin_enqueue_scripts'])) {
        throw new RuntimeException('Unexpected admin menu or asset hook registration.');
    }
    if (isset($GLOBALS['test_hooks']['wp_dashboard_setup']) !== ($context === 'admin')) {
        throw new RuntimeException('Unexpected debug dashboard widget hook registration.');
    }
    if ($context === 'admin') {
        foreach ($GLOBALS['test_hooks']['wp_dashboard_setup'] as $callback) {
            call_user_func($callback);
        }
        if ($GLOBALS['test_dashboard_widgets'] !== array(array('loopis_debug_log', 'debug.log', 'loopis_develooper_render_debug_dashboard_widget'))) {
            throw new RuntimeException('Incorrect debug dashboard widget configuration.');
        }
    }
    if ($authorized) {
        if (!get_sample_posts() || !get_sample_users()) {
            throw new RuntimeException('Sample post and user data must be loaded.');
        }
        $sample_posts = get_sample_posts();
        $post_slugs = array_column($sample_posts, 'post_name');
        foreach ($sample_posts as $post) {
            if (isset($post['reuse_images_from'])) {
                if (!in_array($post['reuse_images_from'], $post_slugs, true)) {
                    throw new RuntimeException('Sample image source does not exist: ' . $post['reuse_images_from']);
                }
                continue;
            }
            $image = dirname(__DIR__, 2) . '/includes/samples/content/images/' . $post['feature_image'] . '.jpg';
            if (!is_file($image)) {
                throw new RuntimeException('Sample post image is missing: ' . $image);
            }
        }
        foreach ($GLOBALS['test_hooks']['admin_menu'] as $callback) {
            call_user_func($callback);
        }
        $slugs = array_column($GLOBALS['test_submenus'], 4);
        $expected_slugs = array('develooper_samples', 'develooper_roles');
        $expected_callbacks = array('develooper_samples_page', 'develooper_roles_page');
        $expected_titles = array('Samples', 'Roles');
        if ($context === 'admin') {
            $expected_slugs = array_merge($expected_slugs, array('loopis-debug-log', 'loopis-error-log'));
            $expected_callbacks = array_merge($expected_callbacks, array('loopis_develooper_render_debug_log', 'loopis_develooper_render_error_log'));
            $expected_titles = array_merge($expected_titles, array('debug.log', 'error_log'));
        }
        if ($slugs !== $expected_slugs
            || array_column($GLOBALS['test_submenus'], 5) !== $expected_callbacks
            || array_column($GLOBALS['test_submenus'], 1) !== $expected_titles
            || array_column($GLOBALS['test_submenus'], 2) !== $expected_titles
            || $GLOBALS['test_menu'][1] !== 'Develooper' || $GLOBALS['test_menu'][2] !== 'manage_options'
            || $GLOBALS['test_menu'][4] !== ''
            || $GLOBALS['test_menu'][5] !== 'https://example.test/loopis-develooper/assets/loopis-icon.png'
            || !is_file(dirname(__DIR__, 2) . '/assets/loopis-icon.png')) {
            throw new RuntimeException('Developer menu contains unexpected items or callback.');
        }
        foreach ($GLOBALS['test_submenus'] as $submenu) {
            if ($submenu[3] !== 'manage_options') {
                throw new RuntimeException('Developer submenu is missing the required capability.');
            }
            ob_start();
            develooper_samples_page();
            $page = ob_get_clean();
            foreach (array('name="insert_samples"', 'name="reset_samples"', 'name="delete_samples"', 'class="button button-primary"') as $expected) {
                if (strpos($page, $expected) === false) {
                    throw new RuntimeException('Sample page is missing ' . $expected);
                }
            }
            if (strpos($page, 'button_loading') !== false || strpos($page, 'loading-btn') !== false || strpos($page, 'coin.png') !== false) {
                throw new RuntimeException('Sample page still uses the custom loader.');
            }
            if (strpos($page, '<p class="description">Samples are defined in the folder "content" within the plugin directory.</p>') === false) {
                throw new RuntimeException('Sample description is missing the WordPress description class.');
            }
            foreach (get_sample_users() as $user) {
                $name = trim($user['first_name'] . ' ' . $user['last_name']);
                if (strpos($page, '<td>' . esc_html($name) . '</td>') === false) {
                    throw new RuntimeException('Sample page is missing user ' . $name);
                }
            }
        }
    }
    echo "Plugin loading test passed: $context.\n";
} finally {
    unlink($fixture . '/wp-includes/pluggable.php');
    rmdir($fixture . '/wp-includes');
    foreach (array('post.php', 'user.php', 'ms.php') as $file) {
        unlink($fixture . '/wp-admin/includes/' . $file);
    }
    rmdir($fixture . '/wp-admin/includes');
    rmdir($fixture . '/wp-admin');
    rmdir($fixture);
}
