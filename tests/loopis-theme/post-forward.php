<?php
/** Run from loopis-develooper with: php tests/loopis-theme/post-forward.php */

define('ABSPATH', __DIR__ . '/');

function get_post($id) { return (object) array('post_title' => 'Sample', 'post_content' => 'Content', 'post_type' => 'post'); }
function get_current_user_id() { return 7; }
function get_post_meta($id, $key = '', $single = false) {
    $meta = array('image_2' => array('102'), 'image_3' => array('103'), 'location' => array('Original'), 'unrelated' => array('ignored'));
    return $key === '' ? $meta : ($meta[$key][0] ?? '');
}
function wp_insert_post($post) { return 20; }
function is_wp_error($value) { return false; }
function loopis_ledger_add_post(...$args) {}
function current_time($format) { return '2026-11-16 12:00:00'; }
function get_post_thumbnail_id($id) { return 101; }
function set_post_thumbnail($id, $image) { $GLOBALS['forward_test_thumbnail'] = array($id, $image); }
function maybe_unserialize($value) { return $value; }
function update_post_meta($id, $key, $value) { $GLOBALS['forward_test_meta'][$id][$key] = $value; }
function wp_get_post_tags($id) { return array(); }
function delete_post_meta($id, $key) {}
function get_permalink($id) { return 'https://example.test/' . $id; }
function esc_url($url) { return $url; }
function add_comment($content, $id) {}
function home_url($path) { return 'https://example.test' . $path; }
function add_query_arg($args, $url) { return $url . '?' . http_build_query($args); }
function wp_safe_redirect($url) {
    $meta = $GLOBALS['forward_test_meta'];
    if ($GLOBALS['forward_test_thumbnail'] !== array(20, 101)
        || $meta[20]['image_2'] !== '102' || $meta[20]['image_3'] !== '103'
        || isset($meta[20]['unrelated']) || $meta[20]['previous_post'] !== 10
        || $meta[10]['forward_post'] !== 20) {
        throw new RuntimeException('Forwarded posts must reuse image IDs and retain forwarding metadata.');
    }
    throw new RuntimeException('forward_test_complete');
}

$theme = dirname(__DIR__, 3) . '/loopis-theme/includes/functions/user-extra/post-action-forward.php';
require $theme;
try {
    action_forward(10);
    throw new RuntimeException('Expected redirect.');
} catch (RuntimeException $error) {
    if ($error->getMessage() !== 'forward_test_complete') {
        throw $error;
    }
}
echo "Post forwarding test passed.\n";
