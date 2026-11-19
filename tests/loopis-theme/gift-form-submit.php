<?php
/** Run with: php tests/loopis-theme/gift-form-submit.php create|reorder|rotate|upload-failure|invalid|stale */
$mode = $argv[1] ?? '';
if (!in_array($mode, array('create', 'reorder', 'rotate', 'upload-failure', 'invalid', 'stale'), true)) {
    throw new RuntimeException('Invalid test mode.');
}
$fixture = sys_get_temp_dir() . '/loopis-gift-submit-' . bin2hex(random_bytes(8));
if (!mkdir($fixture . '/wp-admin/includes', 0700, true)) { throw new RuntimeException('Fixture creation failed.'); }
foreach (array('file.php', 'media.php', 'image.php') as $file) {
    file_put_contents($fixture . '/wp-admin/includes/' . $file, "<?php\n");
}
define('ABSPATH', $fixture . '/');
define('LOOPIS_THEME_DIR', dirname(__DIR__, 3) . '/loopis-theme');
define('LOOPIS_THEME_URI', 'https://example.test/theme');
class WP_Error {
    private $message;
    public function __construct($code, $message) { $this->message = $message; }
    public function get_error_message() { return $this->message; }
}
function is_wp_error($value) { return $value instanceof WP_Error; }
function wp_enqueue_script(...$args) {}
function wp_unslash($value) { return $value; }
function sanitize_text_field($value) { return $value; }
function sanitize_textarea_field($value) { return $value; }
function sanitize_file_name($value) { return $value; }
function get_tags($args) { return array(); }
function current_user_can($capability, ...$args) { return in_array($capability, array('edit_post', 'publish_posts'), true); }
function absint($value) { return abs((int) $value); }
function get_permalink($id) { return 'https://example.test/post/' . $id; }
function get_queried_object_id() { return 1; }
function wp_get_post_terms($id, $taxonomy, $args) { return array(1); }
function get_post($id) { return (object) array('post_type' => 'post'); }
function get_post_thumbnail_id($id) { return $GLOBALS['submit_meta'][$id]['_thumbnail_id'] ?? 0; }
function get_post_meta($id, $key, $single) { return $GLOBALS['submit_meta'][$id][$key] ?? ''; }
function wp_get_attachment_image_url($id, $size) { return 'https://example.test/' . $id . '.webp'; }
function wp_get_attachment_url($id) { return 'https://example.test/' . $id . '.webp'; }
function wp_parse_url($url, $component) { return parse_url($url, $component); }
function get_attached_file($id) { return '/uploads/' . $id . '.webp'; }
function esc_url_raw($url) { return $url; }
function wp_verify_nonce($nonce, $action) { return true; }
function media_handle_sideload($file, $post_id) {
    $GLOBALS['submit_uploads'][] = $file['name'];
    if ($GLOBALS['mode'] === 'upload-failure' && count($GLOBALS['submit_uploads']) === 2) {
        return new WP_Error('upload', 'Upload failed');
    }
    return 201 + count($GLOBALS['submit_uploads']) - 1;
}
function wp_insert_post($post, $error) { return 12; }
function wp_update_post($post, $error) { $GLOBALS['submit_updated'] = true; return $post['ID']; }
function set_post_thumbnail($id, $value) { $GLOBALS['submit_meta'][$id]['_thumbnail_id'] = $value; }
function delete_post_thumbnail($id) { unset($GLOBALS['submit_meta'][$id]['_thumbnail_id']); }
function update_post_meta($id, $key, $value) { $GLOBALS['submit_meta'][$id][$key] = $value; }
function delete_post_meta($id, $key) { unset($GLOBALS['submit_meta'][$id][$key]); }
function wp_delete_attachment($id, $force) { $GLOBALS['submit_deleted'][] = $id; return true; }
function wp_set_post_terms(...$args) {}
function get_current_user_id() { return 7; }
function add_query_arg($args, $url) { return $url . '?' . http_build_query($args); }
function wp_safe_redirect($url) { throw new RuntimeException('redirect'); }
function esc_html($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function wp_nonce_field(...$args) { throw new RuntimeException('render'); }

$GLOBALS['submit_meta'] = array(
    10 => array('_thumbnail_id' => 101, 'image_2' => 102, 'image_3' => 103),
    11 => array('_thumbnail_id' => 101, 'image_2' => 102, 'image_3' => 103),
);
$GLOBALS['submit_uploads'] = array();
$GLOBALS['submit_deleted'] = array();
$GLOBALS['submit_updated'] = false;
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = array(
    'submit_gift_post' => '1', 'loopis_gift_form_nonce' => 'test', 'edit_post_id' => '10',
    'post_title' => 'Title', 'post_content' => 'Description', 'terms' => array(1), 'locker' => '1',
    'featured_image_index' => in_array($mode, array('reorder', 'create'), true) ? '1' : '0',
    'gift_original_images' => $mode === 'stale' ? '[101,102]' : '[101,102,103]',
    'gift_image_selection' => in_array($mode, array('reorder', 'stale'), true) ? '[{"id":101},{"id":102},{"id":103}]'
        : ($mode === 'invalid' ? '[{"id":999}]'
            : (in_array($mode, array('upload-failure', 'create'), true) ? '[{"upload":0},{"upload":1}]' : '[{"upload":0},{"id":102}]')),
);
if ($mode === 'create') { unset($_POST['edit_post_id']); }
$_FILES = array();
if (in_array($mode, array('rotate', 'upload-failure', 'create'), true)) {
    $count = $mode === 'rotate' ? 1 : 2;
    $_FILES['images'] = array(
        'name' => array_fill(0, $count, 'image.webp'), 'type' => array_fill(0, $count, 'image/webp'),
        'tmp_name' => array_fill(0, $count, '/tmp/image'), 'error' => array_fill(0, $count, UPLOAD_ERR_OK),
        'size' => array_fill(0, $count, 1),
    );
}
try {
    ob_start();
    try {
        require LOOPIS_THEME_DIR . '/templates/forms/gift-form.php';
        throw new RuntimeException('Form did not redirect or render.');
    } catch (RuntimeException $error) {
        $expected = in_array($mode, array('create', 'reorder', 'rotate'), true) ? 'redirect' : 'render';
        if ($error->getMessage() !== $expected) { throw $error; }
    } finally {
        ob_end_clean();
    }
    $original = array('_thumbnail_id' => 101, 'image_2' => 102, 'image_3' => 103);
    if ($GLOBALS['submit_meta'][11] !== $original) { throw new RuntimeException('Shared post changed.'); }
    if ($mode === 'create' && ($GLOBALS['submit_uploads'] !== array('p12-2.webp', 'p12-1.webp')
        || $GLOBALS['submit_meta'][12]['_thumbnail_id'] !== 202 || $GLOBALS['submit_meta'][12]['image_2'] !== 201)) {
        throw new RuntimeException('New posts must upload and order all selected images.');
    }
    if ($mode === 'reorder' && ($GLOBALS['submit_uploads'] !== array()
        || $GLOBALS['submit_meta'][10]['_thumbnail_id'] !== 102 || $GLOBALS['submit_meta'][10]['image_2'] !== 101
        || $GLOBALS['submit_meta'][10]['image_3'] !== 103)) {
        throw new RuntimeException('Reordering must reuse all IDs without uploading.');
    }
    if ($mode === 'rotate' && (count($GLOBALS['submit_uploads']) !== 1
        || $GLOBALS['submit_meta'][10]['_thumbnail_id'] !== 201 || $GLOBALS['submit_meta'][10]['image_2'] !== 102
        || isset($GLOBALS['submit_meta'][10]['image_3']))) {
        throw new RuntimeException('Rotation must upload only the changed image.');
    }
    if (in_array($mode, array('upload-failure', 'invalid', 'stale'), true)
        && ($GLOBALS['submit_meta'][10] !== $original || $GLOBALS['submit_updated']
            || ($mode === 'upload-failure' && $GLOBALS['submit_deleted'] !== array(201)))) {
        throw new RuntimeException('Failed submissions must retain existing post images and clean partial uploads.');
    }
    echo "Gift form submission test passed: $mode.\n";
} finally {
    foreach (array('file.php', 'media.php', 'image.php') as $file) { unlink($fixture . '/wp-admin/includes/' . $file); }
    rmdir($fixture . '/wp-admin/includes');
    rmdir($fixture . '/wp-admin');
    rmdir($fixture);
}
