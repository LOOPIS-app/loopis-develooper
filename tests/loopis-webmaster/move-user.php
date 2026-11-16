<?php
/** Run from loopis-develooper with: php tests/loopis-webmaster/move-user.php render|db-error|stack|revive|partial|invalid|same-site|denied */

$mode = $argv[1] ?? '';
if (!in_array($mode, array('render', 'db-error', 'stack', 'revive', 'partial', 'invalid', 'same-site', 'denied'), true)) {
    throw new RuntimeException('Invalid test mode.');
}
define('ABSPATH', __DIR__ . '/');

class WP_Error {
    private $code;
    public function __construct($code, $message) { $this->code = $code; }
    public function get_error_code() { return $this->code; }
}
class WP_User {
    public $roles = array('member');
    public function __construct($id) {}
    public function set_role($role) {}
    public function add_role($role) {}
    public function remove_role($role) {}
}
class MoveUserTestDatabase {
    public $base_prefix = 'wp_';
    public $last_error = '';
    public function get_col($query) {
        if ($GLOBALS['mode'] === 'db-error') {
            $this->last_error = 'Database error';
            return array();
        }
        return array(1, 2);
    }
}
$wpdb = new MoveUserTestDatabase();
$GLOBALS['blog_id'] = 7;
$GLOBALS['blog_stack'] = array();
$GLOBALS['nonce_checked'] = false;
function add_action($hook, $callback) {}
function is_super_admin() { return $GLOBALS['mode'] !== 'denied'; }
function current_user_can($capability) { return $capability === 'manage_options'; }
function wp_die($message) { throw new RuntimeException($message); }
function get_users($args) {
    if ($args !== array('blog_id' => 0)) {
        throw new RuntimeException('User selector is not network-wide.');
    }
    return array((object) array('ID' => 25, 'display_name' => '<Member>'));
}
function get_blog_option($id, $option) { return 'Site ' . $id; }
function admin_url($path) { return 'https://example.test/7/wp-admin/' . $path; }
function esc_url($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function esc_attr($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_html($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function wp_nonce_field($action, $name) { echo '<input name="' . esc_attr($name) . '">'; }
function submit_button($text) { echo '<button>' . esc_html($text) . '</button>'; }
function check_admin_referer($action, $name) {
    if ($action !== 'loopis_move_user_action' || $name !== 'loopis_move_user_nonce') {
        throw new RuntimeException('Incorrect nonce.');
    }
    $GLOBALS['nonce_checked'] = true;
}
function absint($value) { return abs((int) $value); }
function is_wp_error($value) { return $value instanceof WP_Error; }
function add_query_arg($args, $value, $url = null) {
    if (!is_array($args)) {
        $args = array($args => $value);
    } else {
        $url = $value;
    }
    return $url . (strpos($url, '?') === false ? '?' : '&') . http_build_query($args);
}
function wp_safe_redirect($url) {
    $expected_error = $GLOBALS['mode'] === 'invalid' ? 'values_wrong' : 'same_site';
    if (!$GLOBALS['nonce_checked'] || strpos($url, 'page=move-user') === false
        || strpos($url, 'error=' . $expected_error) === false
        || strpos($url, '/7/wp-admin/admin.php') === false) {
        throw new RuntimeException('Unexpected redirect or nonce handling: ' . $url);
    }
    echo "Move user {$GLOBALS['mode']} test passed.\n";
}
function get_site($id) { return in_array($id, array(1, 2), true); }
function is_multisite() { return true; }
function get_sites($args) { return $GLOBALS['mode'] === 'partial' ? array(1) : array(1, 2); }
function switch_to_blog($id) { $GLOBALS['blog_stack'][] = $GLOBALS['blog_id']; $GLOBALS['blog_id'] = $id; }
function restore_current_blog() { $GLOBALS['blog_id'] = array_pop($GLOBALS['blog_stack']); }
function get_post_meta($post_id, $key, $single) { return $key === 'migration_ids' ? array(2 => 99) : ''; }
function current_time($format) { return '2026-11-12 12:00:00'; }
function wp_set_object_terms(...$args) {}
function update_post_meta(...$args) {}
function get_post_field(...$args) { return 25; }
function loopis_ledger_add_post(...$args) {}
function add_admin_comment(...$args) {}
function get_home_url($blog_id, $path) { return 'https://example.test/' . $blog_id . $path; }
function get_user_by($field, $id) { return (object) array('ID' => $id); }
function is_user_member_of_blog($user_id, $blog_id) { return true; }
function update_user_meta(...$args) {}
function loopis_cats($slugs) { return array(123); }
function get_posts($args) {
    if ($GLOBALS['blog_id'] !== 1) {
        throw new RuntimeException('Posts must be queried on the source site.');
    }
    return $GLOBALS['mode'] === 'partial' ? array(50) : array();
}

require dirname(__DIR__, 3) . '/loopis-webmaster/includes/move-user/functions.php';
require dirname(__DIR__, 3) . '/loopis-webmaster/includes/move-user/page.php';

if ($mode === 'render') {
    ob_start();
    loopis_move_user_page();
    $page = ob_get_clean();
    foreach (array('name="loopis_move_user_nonce"', 'value="loopis_move_user"', '&lt;Member&gt;', 'Site 1', 'Site 2') as $expected) {
        if (strpos($page, $expected) === false) {
            throw new RuntimeException('Move User form is missing ' . $expected);
        }
    }
} elseif ($mode === 'db-error') {
    ob_start();
    loopis_move_user_page();
    $page = ob_get_clean();
    if (strpos($page, 'Could not load areas') === false || strpos($page, '<form') !== false) {
        throw new RuntimeException('An areas query failure must be displayed without the form.');
    }
} elseif ($mode === 'stack') {
    $result = migrate_user_active_posts(25, 1, 2);
    if ($result !== array('moved' => array(), 'errors' => array()) || $GLOBALS['blog_id'] !== 7 || $GLOBALS['blog_stack']) {
        throw new RuntimeException('Migration did not restore the invoking site and switch stack.');
    }
} elseif ($mode === 'revive') {
    $result = move_post(50, 1, 2);
    if ($result !== 99 || $GLOBALS['blog_id'] !== 7 || $GLOBALS['blog_stack']) {
        throw new RuntimeException('Reviving a migrated post did not restore the invoking site and switch stack.');
    }
} elseif ($mode === 'partial') {
    $log = tempnam(sys_get_temp_dir(), 'loopis-move-user-test-');
    if ($log === false) {
        throw new RuntimeException('Could not create log fixture.');
    }
    $previous_log = ini_get('error_log');
    try {
        ini_set('error_log', $log);
        $result = move_user(25, 1, 2);
        if (!is_wp_error($result) || $result->get_error_code() !== 'post_migration_partial'
            || strpos(file_get_contents($log), 'post 50 could not be moved') === false
            || $GLOBALS['blog_id'] !== 7 || $GLOBALS['blog_stack']) {
            throw new RuntimeException('Post failures must be logged and reported as a partial move.');
        }
    } finally {
        ini_set('error_log', $previous_log);
        unlink($log);
    }
} elseif ($mode === 'denied') {
    try {
        loopis_handle_move_user();
        throw new RuntimeException('Unauthorized action was not denied.');
    } catch (RuntimeException $error) {
        if ($error->getMessage() !== 'You do not have permission to perform this action.' || $GLOBALS['nonce_checked']) {
            throw $error;
        }
    }
} else {
    $_POST = $mode === 'invalid'
        ? array('user_id' => 0, 'source_blog' => 1, 'target_blog' => 2)
        : array('user_id' => 25, 'source_blog' => 1, 'target_blog' => 1);
    loopis_handle_move_user();
    throw new RuntimeException('Handler failed to exit after redirect.');
}
echo "Move user $mode test passed.\n";
