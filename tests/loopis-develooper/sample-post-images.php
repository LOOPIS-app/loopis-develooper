<?php
/** Run from loopis-develooper with: php tests/loopis-develooper/sample-post-images.php */

$fixture = sys_get_temp_dir() . '/loopis-sample-images-test-' . bin2hex(random_bytes(8));
if (!mkdir($fixture . '/wp-admin/includes', 0700, true) || !mkdir($fixture . '/uploads', 0700)) {
    throw new RuntimeException('Could not create fixture directories.');
}
file_put_contents($fixture . '/wp-admin/includes/image.php', "<?php\n");
file_put_contents($fixture . '/wp-admin/includes/file.php', "<?php\n");
define('ABSPATH', $fixture . '/');
define('OBJECT', 'OBJECT');

class WP_Rewrite {}
class WP_Query {}
class WP_Error {
    private $message;
    public function __construct($code, $message) { $this->message = $message; }
    public function get_error_message() { return $this->message; }
}
function is_wp_error($value) { return $value instanceof WP_Error; }
function post_exists($title) { return true; }
function get_user_by($field, $login) {
    if ($login === 'gabby-giver' && !empty($GLOBALS['image_test_source_missing'])) {
        return false;
    }
    return (object) array('ID' => 42);
}
function current_time($format) { return '2026-11-16 12:00:00'; }
function get_page_by_path($slug, $output, $type) {
    if ($slug === 'pinnstol') {
        return empty($GLOBALS['image_test_source_missing']) ? (object) array('ID' => 11, 'post_title' => 'Pinnstol') : null;
    }
    if ($slug === 'pinnstol_2') {
        return $GLOBALS['image_test_forwarded'] ?? null;
    }
    return (object) array('ID' => 999);
}
function wp_insert_post($post) {
    if ($post['post_name'] !== 'pinnstol_2') { throw new RuntimeException('Unexpected post insertion.'); }
    $GLOBALS['image_test_forwarded'] = (object) array('ID' => 12, 'post_title' => $post['post_title']);
    return 12;
}
function wp_set_post_tags($post_id, $tags, $append) {}
function get_term_by($field, $category, $taxonomy) { return (object) array('term_id' => 1); }
function term_exists($term_id, $taxonomy) { return true; }
function wp_set_post_categories($post_id, $categories, $append) {}
function flush_rewrite_rules($hard) {}
function get_post_thumbnail_id($post_id) { return $GLOBALS['image_test_thumbnails'][$post_id] ?? 0; }
function set_post_thumbnail($post_id, $id) { $GLOBALS['image_test_thumbnails'][$post_id] = $id; return true; }
function get_post_meta($post_id, $key, $single) { return $GLOBALS['image_test_meta'][$post_id][$key] ?? ''; }
function wp_attachment_is_image($id) { return isset($GLOBALS['image_test_attachments'][$id]); }
function loopis_elog_function_start($name) {}
function loopis_elog_first_level($message) { $GLOBALS['image_test_logs'][] = $message; }
function loopis_elog_function_end_success($name) { $GLOBALS['image_test_result'] = 'success'; }
function loopis_elog_function_end_failure($name) { $GLOBALS['image_test_result'] = 'failure'; }
function get_post($id) { return (object) array('post_date' => '2026-11-16 12:00:00'); }
function wp_upload_dir($time) {
    if ($time !== '2026-11-16 12:00:00') { throw new RuntimeException('Incorrect upload date.'); }
    return array('path' => ABSPATH . 'uploads', 'subdir' => '/2026/11', 'error' => false);
}
function wp_unique_filename($dir, $filename) {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $candidate = $filename;
    for ($i = 1; file_exists($dir . '/' . $candidate); $i++) {
        $candidate = $name . '-' . $i . '.' . $ext;
    }
    return $candidate;
}
function wp_check_filetype($filename, $mimes) { return array('type' => 'image/jpeg'); }
function wp_insert_attachment($data, $path, $post_id, $wp_error) {
    if (!empty($GLOBALS['image_test_attachment_failure'])) {
        return new WP_Error('attachment_failed', 'Simulated attachment failure');
    }
    $id = count($GLOBALS['image_test_attachments']) + 100;
    $GLOBALS['image_test_attachments'][$id] = array('path' => $path, 'post_id' => $post_id);
    return $id;
}
function wp_generate_attachment_metadata($id, $path) { return array('file' => basename($path)); }
function wp_update_attachment_metadata($id, $metadata) { $GLOBALS['image_test_generated'][$id] = $metadata; }
function update_post_meta($post_id, $key, $value) { $GLOBALS['image_test_meta'][$post_id][$key] = $value; }

try {
    require dirname(__DIR__, 2) . '/includes/samples/sample_posts_insert.php';
    $GLOBALS['image_test_meta'] = array();
    $GLOBALS['image_test_attachments'] = array();
    $GLOBALS['image_test_generated'] = array();
    $GLOBALS['image_test_logs'] = array();

    $samples = array();
    foreach (get_sample_posts() as $sample) {
        $samples[$sample['post_name']] = $sample;
    }
    foreach (array('pinnstol' => 11) as $slug => $post_id) {
        $sample = $samples[$slug];
        $sample['post_meta'] = array_intersect_key($sample['post_meta'], array_flip(array('image_2', 'image_3', 'location')));
        $result = develooper_postmeta_insert($post_id, $sample);
        if (is_wp_error($result) || $GLOBALS['image_test_result'] !== 'success') {
            throw new RuntimeException('Sample image insertion failed for ' . $slug);
        }
        foreach (array('image_2' => 'post-20b.jpg', 'image_3' => 'post-20c.jpg') as $key => $filename) {
            $id = $GLOBALS['image_test_meta'][$post_id][$key];
            $attachment = $GLOBALS['image_test_attachments'][$id];
            if (!is_int($id) || $attachment['post_id'] !== $post_id
                || !isset($GLOBALS['image_test_generated'][$id])
                || file_get_contents($attachment['path']) !== file_get_contents(dirname(__DIR__, 2) . '/includes/samples/content/images/' . $filename)) {
                throw new RuntimeException('Incorrect attachment for ' . $slug . ' / ' . $key);
            }
        }
    }
    if (count($GLOBALS['image_test_attachments']) !== 2) {
        throw new RuntimeException('The original post must receive its own attachments.');
    }

    $thumbnail = develooper_add_image_to_inserted_post(11, dirname(__DIR__, 2) . '/includes/samples/content/images/post-20.jpg');
    $GLOBALS['image_test_thumbnails'][11] = $thumbnail;
    $attachment_count = count($GLOBALS['image_test_attachments']);
    develooper_sample_posts_insert();
    if (count($GLOBALS['image_test_attachments']) !== $attachment_count
        || $GLOBALS['image_test_thumbnails'][12] !== $thumbnail
        || $GLOBALS['image_test_meta'][12]['image_2'] !== $GLOBALS['image_test_meta'][11]['image_2']
        || $GLOBALS['image_test_meta'][12]['image_3'] !== $GLOBALS['image_test_meta'][11]['image_3']
        || $GLOBALS['image_test_meta'][12]['previous_post'] !== 11
        || $GLOBALS['image_test_meta'][11]['forward_post'] !== 12
        || $GLOBALS['image_test_result'] !== 'success') {
        throw new RuntimeException('Forwarded samples must reuse all original image IDs without creating attachments.');
    }

    $GLOBALS['image_test_forwarded'] = null;
    $GLOBALS['image_test_source_missing'] = true;
    develooper_sample_posts_insert();
    if ($GLOBALS['image_test_forwarded'] !== null || $GLOBALS['image_test_result'] !== 'failure'
        || strpos(implode("\n", $GLOBALS['image_test_logs']), 'Image source post not found: pinnstol') === false) {
        throw new RuntimeException('A missing image source must prevent insertion and be logged.');
    }
    $GLOBALS['image_test_source_missing'] = false;
    $original_image_3 = $GLOBALS['image_test_meta'][11]['image_3'];
    $GLOBALS['image_test_meta'][11]['image_3'] = 'post-20c';
    develooper_sample_posts_insert();
    if ($GLOBALS['image_test_forwarded'] !== null || $GLOBALS['image_test_result'] !== 'failure'
        || strpos(implode("\n", $GLOBALS['image_test_logs']), 'Missing or invalid image_3 attachment') === false) {
        throw new RuntimeException('Invalid source attachments must prevent insertion and be logged.');
    }
    $GLOBALS['image_test_meta'][11]['image_3'] = $original_image_3;

    $missing = array('post_title' => 'Missing image', 'post_meta' => array('image_2' => 'missing-test-image', 'location' => 'test'));
    $result = develooper_postmeta_insert(13, $missing);
    if (!is_wp_error($result) || $GLOBALS['image_test_result'] !== 'failure'
        || isset($GLOBALS['image_test_meta'][13]['image_2']) || $GLOBALS['image_test_meta'][13]['location'] !== 'test'
        || strpos(implode("\n", $GLOBALS['image_test_logs']), 'Failed to attach image_2') === false) {
        throw new RuntimeException('Missing images must be reported without saving filenames.');
    }

    $GLOBALS['image_test_attachment_failure'] = true;
    $result = develooper_postmeta_insert(14, array('post_title' => 'Failed image', 'post_meta' => array('image_3' => 'post-20c')));
    if (!is_wp_error($result) || isset($GLOBALS['image_test_meta'][14]['image_3'])
        || strpos($result->get_error_message(), 'Simulated attachment failure') === false) {
        throw new RuntimeException('Attachment failures must be reported without saving filenames.');
    }

    echo "Sample post image tests passed.\n";
} finally {
    foreach ($GLOBALS['image_test_attachments'] ?? array() as $attachment) {
        unlink($attachment['path']);
    }
    unlink($fixture . '/wp-admin/includes/image.php');
    unlink($fixture . '/wp-admin/includes/file.php');
    rmdir($fixture . '/wp-admin/includes');
    rmdir($fixture . '/wp-admin');
    rmdir($fixture . '/uploads');
    rmdir($fixture);
}
