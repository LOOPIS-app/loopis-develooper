<?php
/**
 * Run from loopis-develooper with: php tests/loopis-webmaster/image-migration-directories.php
 * Exercises directory scanning and cleanup without a WordPress installation.
 */

define('ABSPATH', __DIR__ . '/');
define('DAY_IN_SECONDS', 86400);

class WP_Error {
    private $code;
    private $message;

    public function __construct($code, $message) {
        $this->code = $code;
        $this->message = $message;
    }

    public function get_error_code() {
        return $this->code;
    }

    public function get_error_message() {
        return $this->message;
    }
}

class Migration_Test_Image_Editor {
    public $quality;
    public static function supports_mime_type($mime_type) { return $mime_type === 'image/webp'; }
    public function maybe_exif_rotate() { return true; }
    public function get_size() { return array('width' => 100, 'height' => 100); }
    public function set_quality($quality) {
        $this->quality = $quality;
        return new WP_Error('test_stop', 'Stop after quality selection.');
    }
}

class Migration_Test_DB {
    public $prefix = 'app_';
    public $dbname = 'test';
    public $last_error = '';
    public $insert_id = 0;
    public $items = array();
    public $locked = false;
    public $posts = 'app_posts';
    public $postmeta = 'app_postmeta';
    public $file_lookup_result = null;
    public $file_lookup_error = false;

    public function prepare($sql, ...$args) {
        return vsprintf(str_replace('%s', "'%s'", $sql), $args);
    }

    public function get_var($sql) {
        if (strpos($sql, "meta.meta_key = '_wp_attached_file'") !== false) {
            if (strpos($sql, 'INNER JOIN app_posts AS posts ON posts.ID = meta.post_id') === false) {
                throw new RuntimeException('File lookup must verify the post exists.');
            }
            $this->last_error = $this->file_lookup_error ? 'Simulated database error' : '';
            return $this->file_lookup_result;
        }
        if (strpos($sql, 'GET_LOCK') !== false) {
            if ($this->locked) {
                return '0';
            }
            $this->locked = true;
            return '1';
        }
        if (strpos($sql, 'RELEASE_LOCK') !== false) {
            $this->locked = false;
            return '1';
        }
        if (preg_match("/status = '([^']+)'/", $sql, $match)) {
            return count(array_filter($this->items, static function($item) use ($match) {
                return $item['status'] === $match[1];
            }));
        }
        throw new RuntimeException('Unexpected query: ' . $sql);
    }

    public function query($sql) {
        if (strpos($sql, "SET status = 'superseded'") !== false) {
            foreach ($this->items as &$item) {
                $item['status'] = 'superseded';
            }
            return count($this->items);
        }
        throw new RuntimeException('Unexpected query: ' . $sql);
    }

    public function insert($table, $data, $format) {
        if ($table !== $this->prefix . 'loopis_image_migration') {
            throw new RuntimeException('Unexpected insert into ' . $table);
        }
        $data['id'] = ++$this->insert_id;
        $this->items[$data['id']] = $data;
        return 1;
    }

    public function get_row($sql) {
        foreach ($this->items as $item) {
            if ($item['status'] === 'pending') {
                return (object) $item;
            }
        }
        return null;
    }

    public function update($table, $data, $where, $format, $where_format) {
        $id = $where['id'];
        if (!isset($this->items[$id]) || (isset($where['status']) && $this->items[$id]['status'] !== $where['status'])) {
            return 0;
        }
        $this->items[$id] = array_merge($this->items[$id], $data);
        return 1;
    }
}

function add_action() {}
function add_filter() {}
function wp_normalize_path($path) { return str_replace('\\', '/', $path); }
function trailingslashit($path) { return rtrim($path, '/\\') . '/'; }
function wp_basename($path) { return basename($path); }
function wp_mkdir_p($path) { return is_dir($path) || mkdir($path, 0777, true); }
function _wp_relative_upload_path($path) { return ltrim(substr($path, strlen($GLOBALS['migration_test_dir'])), '/'); }
function esc_html($text) { return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function wp_upload_dir() { return array('basedir' => $GLOBALS['migration_test_dir']); }
function get_posts($args) { return $GLOBALS['migration_test_attachments'] ?? array(); }
function wp_image_editor_supports($args) { return true; }
function get_option($name) { return array('large_size_w' => 1600, 'large_size_h' => 1600)[$name] ?? false; }
function absint($value) { return abs((int) $value); }
function get_attached_file($id) { return $GLOBALS['migration_test_files'][$id] ?? false; }
function wp_get_attachment_metadata($id) { return $GLOBALS['migration_test_metadata'][$id] ?? array(); }
function wp_delete_attachment($id, $force) { $GLOBALS['migration_test_deleted_attachments'][] = $id; return true; }
function get_current_blog_id() { return 1; }
function current_time($type, $gmt) { return '2026-01-01 00:00:00'; }
function sanitize_key($value) { return $value; }
function sanitize_textarea_field($value) { return $value; }
function wp_json_encode($value) { return json_encode($value); }
function wp_get_image_editor($file) { return $GLOBALS['migration_test_editor']; }
function wp_delete_file($file) { return unlink($file); }
function set_transient($key, $value, $expiry) { $GLOBALS['migration_test_transients'][$key] = $value; }
function is_wp_error($value) { return $value instanceof WP_Error; }
function get_post($id) { return $GLOBALS['migration_test_posts'][$id] ?? null; }
function get_post_thumbnail_id($id) { return $id === 10 ? 101 : ($id === 20 ? 201 : 0); }
function get_post_meta($id, $key, $single) {
    return $id === 10 ? (array('image_2' => 102, 'image_3' => 103)[$key] ?? 0) : 0;
}
function sanitize_title($title) { return trim(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title)), '-'); }
function wp_unique_filename($directory, $filename) {
    $base = pathinfo($filename, PATHINFO_FILENAME);
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $candidate = $filename;
    for ($number = 1; file_exists($directory . '/' . $candidate); $number++) {
        $candidate = $base . '-' . $number . '.' . $extension;
    }
    return $candidate;
}

function migration_test_assert($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function migration_test_remove_fixture($directory) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $entry) {
        if ($entry->isDir() && !$entry->isLink()) {
            rmdir($entry->getPathname());
        } else {
            unlink($entry->getPathname());
        }
    }
    rmdir($directory);
}

$fixture = sys_get_temp_dir() . '/loopis-migration-test-' . bin2hex(random_bytes(8));
mkdir($fixture);
$fixture = realpath($fixture);
define('LOOPIS_IMAGE_MIGRATION_LOG_DIR', $fixture . '/private-log');
require dirname(__DIR__, 3) . '/loopis-webmaster/includes/image-migration/functions.php';
require dirname(__DIR__, 3) . '/loopis-webmaster/includes/image-migration/page.php';
require dirname(__DIR__, 3) . '/loopis-content/functions/loopis_image_uploads.php';
$GLOBALS['migration_test_dir'] = $fixture . '/uploads';
$wpdb = new Migration_Test_DB();

try {
    migration_test_assert(loopis_media_image_quality(82, 'image/webp') === 75, 'WebP thumbnail quality must be 75.');
    migration_test_assert(loopis_media_image_quality(82, 'image/jpeg') === 70 && loopis_media_image_quality(82, 'image/png') === 70, 'JPEG and PNG quality must remain unchanged.');
    migration_test_assert(loopis_media_image_quality(82, 'image/gif') === 82, 'Other formats must keep their original quality.');
    $upload_source = $fixture . '/upload.jpg';
    file_put_contents($upload_source, 'upload');
    $GLOBALS['migration_test_editor'] = new Migration_Test_Image_Editor();
    $upload_result = loopis_process_media_upload(array('file' => $upload_source, 'type' => 'image/jpeg'));
    migration_test_assert(isset($upload_result['error']) && $GLOBALS['migration_test_editor']->quality === 75, 'Full-size uploads must select WebP quality 75.');
    $base = $GLOBALS['migration_test_dir'];
    foreach (array('2024/01', '2024/02', '2026/sites', 'sites/2/2024/01', 'sites/2/empty', 'wp-statistics/cache', 'wp-statistics/empty', 'migration/quarantine/1', '.loopis-image-migration-staging/1', 'empty/inner', 'outside') as $directory) {
        mkdir($base . '/' . $directory, 0777, true);
    }
    foreach (array('2024/01/own.jpg', '2026/sites/own.png', 'sites/2/2024/01/subsite.jpg', 'wp-statistics/cache/chart.png', 'migration/quarantine/1/new.jpg', '.loopis-image-migration-staging/1/old.jpg', 'outside/.keep') as $file) {
        file_put_contents($base . '/' . $file, 'fixture');
    }
    mkdir($fixture . '/external');
    file_put_contents($fixture . '/external/image.jpg', 'outside');
    migration_test_assert(symlink($fixture . '/external', $base . '/linked'), 'Could not create symlink fixture.');
    mkdir($base . '/symlink-only');
    migration_test_assert(symlink($fixture . '/external', $base . '/symlink-only/link'), 'Could not create nested symlink fixture.');

    $GLOBALS['migration_test_posts'] = array(
        10 => (object) array('post_type' => 'post', 'post_title' => 'A Gift'),
        11 => (object) array('post_type' => 'post', 'post_title' => ''),
        20 => (object) array('post_type' => 'event', 'post_title' => 'Summer Fair'),
        21 => (object) array('post_type' => 'event', 'post_title' => 'Summer Fair'),
        22 => (object) array('post_type' => 'event', 'post_title' => ''),
    );
    $naming_dir = $fixture . '/external';
    migration_test_assert(loopis_image_migration_format_path('/uploads/<folder>/a&b.webp') === '/uploads/&lt;folder&gt;/<strong>a&amp;b.webp</strong>', 'Path preview must escape its directory and emphasize its filename.');
    migration_test_assert(loopis_image_migration_format_path('') === '', 'Empty paths must remain empty.');
    $reserved = array();
    migration_test_assert(loopis_image_migration_target_path(101, 10, $naming_dir . '/gift.jpg', $reserved) === $naming_dir . '/p10-1.webp', 'Gift featured image filename is incorrect.');
    migration_test_assert(loopis_image_migration_target_path(102, 10, $naming_dir . '/gift2.jpg', $reserved) === $naming_dir . '/p10-2.webp', 'Gift second slot filename is incorrect.');
    migration_test_assert(loopis_image_migration_target_path(103, 10, $naming_dir . '/gift3.jpg', $reserved) === $naming_dir . '/p10-3.webp', 'Gift third slot filename is incorrect.');
    migration_test_assert(loopis_image_migration_target_path(104, 10, $naming_dir . '/other.jpg', $reserved) === $naming_dir . '/a-gift-104.webp', 'Other regular post filename is incorrect.');
    migration_test_assert(loopis_image_migration_target_path(105, 11, $naming_dir . '/untitled.jpg', $reserved) === $naming_dir . '/post-11-105.webp', 'Untitled regular post fallback is incorrect.');
    migration_test_assert(loopis_image_migration_target_path(104, 10, $naming_dir . '/other-again.jpg', $reserved) === $naming_dir . '/a-gift-104-1.webp', 'Reserved regular post attachment filenames must not collide.');
    migration_test_assert(loopis_image_migration_target_path(101, 10, $naming_dir . '/other-gift.jpg', $reserved) === $naming_dir . '/p10-1-1.webp', 'Reserved gift filenames must not collide.');
    migration_test_assert(loopis_image_migration_target_path(201, 20, $naming_dir . '/event.jpg', $reserved) === $naming_dir . '/summer-fair.webp', 'Custom post featured image should use its title.');
    migration_test_assert(loopis_image_migration_target_path(202, 20, $naming_dir . '/event2.jpg', $reserved) === $naming_dir . '/summer-fair-2.webp', 'Planned target collision should start at -2.');
    file_put_contents($naming_dir . '/summer-fair-3.webp', 'existing');
    migration_test_assert(loopis_image_migration_target_path(203, 21, $naming_dir . '/event3.jpg', $reserved) === $naming_dir . '/summer-fair-4.webp', 'Existing and planned targets must both be avoided.');
    migration_test_assert(loopis_image_migration_target_path(204, 22, $naming_dir . '/empty.jpg', $reserved) === $naming_dir . '/post-22.webp', 'Empty title fallback is incorrect.');
    $same_file_reserved = array();
    file_put_contents($naming_dir . '/summer-fair.webp', 'existing');
    migration_test_assert(loopis_image_migration_target_path(201, 20, $naming_dir . '/summer-fair.webp', $same_file_reserved) === $naming_dir . '/summer-fair.webp', 'Already correctly named WebP should retain its filename.');
    $existing_file_reserved = array();
    migration_test_assert(loopis_image_migration_target_path(205, 20, $naming_dir . '/new.jpg', $existing_file_reserved) === $naming_dir . '/summer-fair-2.webp', 'An existing title filename should start numbering at -2.');

    loopis_image_migration_scan();
    $scan_logs = array_values(array_filter(loopis_image_migration_recent_activity(loopis_image_migration_log_file()), static function($entry) { return $entry->status === 'scanned'; }));
    migration_test_assert(count($scan_logs) === 1 && count(explode("\n", $scan_logs[0]->message)) === 7, 'Scan summary must put its heading, counts, and cleanup notice on separate lines.');
    migration_test_assert(strpos($scan_logs[0]->message, "0 post-attached images found.\n0 already-matching WebP images skipped.") !== false, 'Scan summary must report found post-attached images followed by matching WebP skips.');
    migration_test_assert(strpos($scan_logs[0]->message, "2 empty upload folders found; cleanup queued last as one item.") !== false, 'Scan must count only currently empty, in-scope folders without treating symlink-only folders as empty.');
    $pending = array_values(array_filter($wpdb->items, static function($item) { return $item['status'] === 'pending'; }));
    migration_test_assert(count($pending) === 3, 'Scan should queue two local images and one cleanup item.');
    migration_test_assert(array_column($pending, 'item_type') === array('orphan', 'orphan', 'cleanup'), 'Cleanup must be the final queue item.');
    $sources = array_column(array_slice($pending, 0, 2), 'source_path');
    sort($sources);
    migration_test_assert($sources === array($base . '/2024/01/own.jpg', $base . '/2026/sites/own.png'), 'Scan included an excluded directory or missed a local image.');
    migration_test_assert(!loopis_image_migration_path_is_in_scope($base . '/sites/2/2024/01/subsite.jpg', $base), 'Subsite file must not be in scope.');

    loopis_image_migration_scan();
    $pending = array_values(array_filter($wpdb->items, static function($item) { return $item['status'] === 'pending'; }));
    migration_test_assert(count($pending) === 3, 'Rescanning with protected directories must succeed.');
    $processing_id = null;
    foreach ($wpdb->items as &$item) {
        if ($item['status'] === 'pending' && $item['item_type'] === 'orphan') {
            $processing_id = $item['id'];
            $item['status'] = 'completed';
        }
    }
    unset($item);

    $wpdb->locked = true;
    $busy = loopis_image_migration_process_next();
    migration_test_assert(is_wp_error($busy) && $busy->get_error_code() === 'migration_busy', 'Concurrent processing must be blocked.');
    $wpdb->locked = false;
    $wpdb->items[$processing_id]['status'] = 'processing';
    $blocked = loopis_image_migration_process_next();
    migration_test_assert(is_wp_error($blocked) && $blocked->get_error_code() === 'processing_incomplete', 'Cleanup must not run while an item is still processing.');
    migration_test_assert(is_dir($base . '/empty/inner') && !$wpdb->locked, 'Blocked cleanup must not remove folders or retain the lock.');
    $wpdb->items[$processing_id]['status'] = 'completed';
    $result = loopis_image_migration_process_next();
    migration_test_assert(!is_wp_error($result) && $result['status'] === 'completed' && $result['pending'] === 0, 'Final cleanup queue item failed.');
    migration_test_assert(!is_dir($base . '/2024/02') && !is_dir($base . '/empty'), 'Empty nested directories were not removed.');
    migration_test_assert(is_dir($base . '/2024/01') && is_file($base . '/outside/.keep'), 'Non-empty directories must be kept.');
    migration_test_assert(is_file($base . '/sites/2/2024/01/subsite.jpg') && is_file($base . '/wp-statistics/cache/chart.png'), 'Protected files must be kept.');
    migration_test_assert(is_dir($base . '/sites/2/empty') && is_dir($base . '/wp-statistics/empty'), 'Empty folders in protected directories must be kept.');
    migration_test_assert(is_dir($base . '/migration/quarantine/1') && is_dir($base . '/.loopis-image-migration-staging/1'), 'Migration work directories must be kept.');
    migration_test_assert(is_link($base . '/linked') && is_file($fixture . '/external/image.jpg'), 'Symlinks and their targets must be kept.');
    migration_test_assert(is_link($base . '/symlink-only/link'), 'Folders containing symlinks must not be deleted.');
    migration_test_assert(is_dir($base) && !$wpdb->locked, 'Uploads root must remain and the lock must be released.');

    $base = $fixture . '/rescan';
    mkdir($base);
    $GLOBALS['migration_test_dir'] = $base;
    $wpdb = new Migration_Test_DB();
    $cases = array(
        301 => array(31, 'Matching', 'matching.webp', 1600, 100, 'image/webp', 'skipped'),
        302 => array(32, 'Oversized', 'oversized.webp', 1601, 100, 'image/webp', 'pending'),
        303 => array(33, 'Wrong name', 'different.webp', 100, 100, 'image/webp', 'pending'),
        304 => array(34, 'Numbered', 'numbered-2.webp', 100, 1600, 'image/webp', 'skipped'),
        305 => array(35, 'Wrong mime', 'wrong-mime.webp', 100, 100, 'image/jpeg', 'pending'),
        306 => array(36, 'Too tall', 'too-tall.webp', 100, 1601, 'image/webp', 'pending'),
    );
    $GLOBALS['migration_test_attachments'] = array();
    $GLOBALS['migration_test_files'] = array();
    foreach ($cases as $id => $case) {
        list($parent, $title, $filename, $width, $height, $mime) = $case;
        $image = imagecreatetruecolor($width, $height);
        imagewebp($image, $base . '/' . $filename);
        imagedestroy($image);
        $GLOBALS['migration_test_posts'][$parent] = (object) array('post_type' => 'event', 'post_title' => $title);
        $GLOBALS['migration_test_attachments'][] = (object) array('ID' => $id, 'post_parent' => $parent, 'post_mime_type' => $mime);
        $GLOBALS['migration_test_files'][$id] = $base . '/' . $filename;
    }
    loopis_image_migration_scan();
    $items_by_attachment = array();
    foreach ($wpdb->items as $item) {
        if ($item['attachment_id']) {
            $items_by_attachment[$item['attachment_id']] = $item;
        }
    }
    foreach ($cases as $id => $case) {
        migration_test_assert(isset($items_by_attachment[$id]) && $items_by_attachment[$id]['status'] === $case[6], 'Unexpected scan status for attachment ' . $id);
    }
    $recent = loopis_image_migration_recent_activity(loopis_image_migration_log_file());
    migration_test_assert(strpos($recent[0]->message, '2 already-matching WebP images skipped.') !== false, 'Matching WebP count was not reported.');
    migration_test_assert(!loopis_image_migration_webp_is_within_limits($base . '/matching.webp', 'image/webp', 0, 1600), 'Invalid limits must not qualify for skipping.');
    file_put_contents($base . '/invalid.webp', 'not an image');
    migration_test_assert(!loopis_image_migration_webp_is_within_limits($base . '/invalid.webp', 'image/webp', 1600, 1600), 'Invalid WebP data must not qualify for skipping.');

    $wpdb = new Migration_Test_DB();
    $GLOBALS['migration_test_attachments'] = array();
    $GLOBALS['migration_test_files'] = array();
    foreach (array(101 => 'p10-img-1.webp', 102 => 'p10-2.webp', 103 => 'p10-3-1.webp', 104 => 'a-gift-image-104.webp', 105 => 'a-gift-105.webp') as $id => $filename) {
        $image = imagecreatetruecolor(10, 10);
        imagewebp($image, $base . '/' . $filename);
        imagedestroy($image);
        $GLOBALS['migration_test_attachments'][] = (object) array('ID' => $id, 'post_parent' => 10, 'post_mime_type' => 'image/webp');
        $GLOBALS['migration_test_files'][$id] = $base . '/' . $filename;
    }
    loopis_image_migration_scan();
    $items_by_attachment = array();
    foreach ($wpdb->items as $item) {
        if ($item['attachment_id']) {
            $items_by_attachment[$item['attachment_id']] = $item;
        }
    }
    migration_test_assert($items_by_attachment[101]['status'] === 'pending' && $items_by_attachment[101]['target_path'] === $base . '/p10-1.webp', 'An old gift filename must be queued for the new name.');
    migration_test_assert($items_by_attachment[102]['status'] === 'skipped' && $items_by_attachment[103]['status'] === 'skipped', 'Correctly named gift images, including collision suffixes, must be skipped on rescan.');
    migration_test_assert($items_by_attachment[104]['status'] === 'pending' && $items_by_attachment[104]['target_path'] === $base . '/a-gift-104.webp', 'Old regular post filenames must be queued for the new name.');
    migration_test_assert($items_by_attachment[105]['status'] === 'skipped', 'A correctly named regular post attachment must be skipped on rescan.');

    $wpdb = new Migration_Test_DB();
    $failed_path = $base . '/odling-240x240.jpg';
    loopis_image_migration_insert_item('unknown', 0, $failed_path, '', array());
    $result = loopis_image_migration_process_next();
    $expected_message = "The queue item has an unknown action.\nSource: " . $failed_path;
    migration_test_assert($result['status'] === 'failed' && $result['message'] === $expected_message, 'Failure response must include the source path.');
    $recent = loopis_image_migration_recent_activity(loopis_image_migration_log_file());
    migration_test_assert($recent[0]->message === $expected_message, 'Failure log must retain the source path.');
    migration_test_assert(loopis_image_migration_error_details($expected_message, $failed_path) === $expected_message, 'Rendering a recorded failure must not duplicate its source.');
    migration_test_assert(loopis_image_migration_error_details('Older error.', $failed_path) === "Older error.\nSource: " . $failed_path, 'Older failures must display their queue item source.');
    migration_test_assert(loopis_image_migration_error_details('Site-wide error.', '') === 'Site-wide error.', 'Site-wide errors without a source must remain unchanged.');

    $wpdb = new Migration_Test_DB();
    $orphan = $base . '/odling.jpg';
    file_put_contents($orphan, 'orphan image');
    loopis_image_migration_insert_item('orphan', 0, $orphan, '', array());
    $result = loopis_image_migration_process_next();
    $destination = $base . '/migration/quarantine/1-odling.jpg';
    migration_test_assert($result['status'] === 'quarantined' && !file_exists($orphan) && file_get_contents($destination) === 'orphan image', 'An orphan with no existing post must be quarantined.');

    mkdir($base . '/another');
    $another_orphan = $base . '/another/odling.jpg';
    file_put_contents($another_orphan, 'another image');
    loopis_image_migration_insert_item('orphan', 0, $another_orphan, '', array());
    $result = loopis_image_migration_process_next();
    migration_test_assert($result['status'] === 'quarantined' && file_get_contents($base . '/migration/quarantine/2-odling.jpg') === 'another image' && file_get_contents($destination) === 'orphan image', 'Different items with the same filename must be stored separately in the shared quarantine directory.');

    $attachment_source = $base . '/parentless.jpg';
    $attachment_thumb = $base . '/parentless-240x240.jpg';
    file_put_contents($attachment_source, 'original');
    file_put_contents($attachment_thumb, 'thumbnail');
    $GLOBALS['migration_test_posts'][500] = (object) array('post_type' => 'attachment', 'post_parent' => 0);
    $GLOBALS['migration_test_metadata'][500] = array('sizes' => array('thumbnail' => array('file' => basename($attachment_thumb))));
    $attachment_item = (object) array('id' => 500, 'item_type' => 'attachment_quarantine', 'attachment_id' => 500, 'source_path' => $attachment_source);
    $attachment_dir = $base . '/migration/quarantine';
    file_put_contents($attachment_dir . '/500-parentless-240x240.jpg', 'existing');
    $collision = loopis_image_migration_quarantine_item($attachment_item);
    migration_test_assert(is_wp_error($collision) && $collision->get_error_code() === 'destination_exists', 'Existing quarantine files must prevent overwriting.');
    migration_test_assert(file_get_contents($attachment_source) === 'original' && file_get_contents($attachment_thumb) === 'thumbnail' && file_get_contents($attachment_dir . '/500-parentless-240x240.jpg') === 'existing' && !file_exists($attachment_dir . '/500-parentless.jpg'), 'A collision must restore earlier moves and preserve the existing file.');
    migration_test_assert(empty($GLOBALS['migration_test_deleted_attachments']), 'A collision must not delete the attachment.');
    unlink($attachment_dir . '/500-parentless-240x240.jpg');
    $result = loopis_image_migration_quarantine_item($attachment_item);
    migration_test_assert(!is_wp_error($result) && file_get_contents($attachment_dir . '/500-parentless.jpg') === 'original' && file_get_contents($attachment_dir . '/500-parentless-240x240.jpg') === 'thumbnail', 'Attachment files must use their item ID and original names in the shared quarantine directory.');
    migration_test_assert($GLOBALS['migration_test_deleted_attachments'] === array(500), 'A successfully quarantined parentless attachment must be deleted.');

    $registered = $base . '/registered.jpg';
    file_put_contents($registered, 'registered image');
    $wpdb->file_lookup_result = 12847;
    loopis_image_migration_insert_item('orphan', 0, $registered, '', array());
    $result = loopis_image_migration_process_next();
    migration_test_assert($result['status'] === 'failed' && is_file($registered) && strpos($result['message'], 'existing post or attachment') !== false, 'A file referenced by an existing post must not be moved.');

    $failed_lookup = $base . '/lookup-failed.jpg';
    file_put_contents($failed_lookup, 'image');
    $wpdb->file_lookup_result = null;
    $wpdb->file_lookup_error = true;
    loopis_image_migration_insert_item('orphan', 0, $failed_lookup, '', array());
    $result = loopis_image_migration_process_next();
    migration_test_assert($result['status'] === 'failed' && is_file($failed_lookup) && strpos($result['message'], 'Could not check') !== false, 'A database lookup failure must not move the file.');

    migration_test_assert(is_file($base . '/migration/.htaccess') && is_file($base . '/migration/index.php'), 'The migration directory must contain access restrictions.');
    $log_file = $fixture . '/private-log/site-1.jsonl';
    $entries = array_map(static function($line) { return json_decode($line, true); }, file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
    migration_test_assert(end($entries)['status'] === 'failed' && strpos(end($entries)['message'], 'Could not check') !== false, 'The activity file must include the last failure.');
    migration_test_assert(!loopis_image_migration_path_is_inside($log_file, $base), 'The log must not be stored in uploads.');
    migration_test_assert((fileperms($log_file) & 0777) === 0600 && (fileperms(dirname($log_file)) & 0777) === 0700, 'New activity logs must use private permissions.');

    $original_log = file_get_contents($log_file);
    file_put_contents($log_file, '');
    migration_test_assert(loopis_image_migration_recent_activity($log_file) === array(), 'An empty log must have no recent activity.');
    for ($i = 0; $i < 70; $i++) {
        migration_test_assert(loopis_image_migration_log($i, 'completed', str_repeat('x', 200)) === true, 'Activity must be written to the file.');
    }
    $recent = loopis_image_migration_recent_activity($log_file);
    migration_test_assert(count($recent) === 50 && $recent[0]->item_id === 69 && $recent[49]->item_id === 20, 'Recent activity must contain the last 50 entries in reverse order across file blocks.');
    file_put_contents($log_file, "invalid json\n", FILE_APPEND);
    $invalid_log = loopis_image_migration_recent_activity($log_file);
    migration_test_assert(is_wp_error($invalid_log) && $invalid_log->get_error_code() === 'log_invalid_entry', 'Invalid log entries must be reported.');
    file_put_contents($log_file, $original_log);

    loopis_image_migration_insert_item('unknown', 0, $base . '/another.jpg', '', array());
    rename($log_file, $log_file . '.saved');
    symlink($log_file . '.saved', $log_file);
    $unsafe_log = loopis_image_migration_process_next();
    migration_test_assert(is_wp_error($unsafe_log) && $unsafe_log->get_error_code() === 'unsafe_log_file', 'Processing must stop if the activity log is a symlink.');
    migration_test_assert(end($wpdb->items)['status'] === 'pending', 'An unsafe log file must leave the item pending.');
    unlink($log_file);
    rename($log_file . '.saved', $log_file);
    echo "Image migration directory tests passed.\n";
} finally {
    migration_test_remove_fixture($fixture);
}
