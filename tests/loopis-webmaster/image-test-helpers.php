<?php
/** Shared WordPress stubs and temporary fixtures for image tool tests. */

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
            return count(array_filter($this->items, static function($item) use ($match, $sql) {
                return $item['status'] === $match[1] && (strpos($sql, "item_type = 'convert'") === false || $item['item_type'] === 'convert');
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
        if ($table !== $this->prefix . 'loopis_image_conversion') {
            throw new RuntimeException('Unexpected insert into ' . $table);
        }
        $data['id'] = ++$this->insert_id;
        $this->items[$data['id']] = $data;
        return 1;
    }

    public function get_row($sql) {
        foreach ($this->items as $item) {
            if ($item['status'] === 'pending' && (strpos($sql, "item_type = 'convert'") === false || $item['item_type'] === 'convert')) {
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
function get_posts($args) {
    return array_slice($GLOBALS['migration_test_attachments'] ?? array(), (($args['paged'] ?? 1) - 1) * ($args['posts_per_page'] ?? 200), $args['posts_per_page'] ?? 200);
}
function wp_image_editor_supports($args) { return true; }
function get_option($name) { return $GLOBALS['migration_test_options'][$name] ?? array('large_size_w' => 1600, 'large_size_h' => 1600)[$name] ?? false; }
function update_option($name, $value, $autoload = null) { $GLOBALS['migration_test_options'][$name] = $value; return true; }
function absint($value) { return abs((int) $value); }
function get_attached_file($id) { return $GLOBALS['migration_test_files'][$id] ?? false; }
function wp_get_attachment_metadata($id) { return $GLOBALS['migration_test_metadata'][$id] ?? array(); }
function wp_delete_attachment($id, $force) {
    if (!empty($GLOBALS['migration_test_delete_failure'])) return false;
    $GLOBALS['migration_test_deleted_attachments'][] = $id;
    return true;
}
function get_current_blog_id() { return 1; }
function current_time($type, $gmt) { return '2026-01-01 00:00:00'; }
function sanitize_key($value) { return $value; }
function sanitize_textarea_field($value) { return $value; }
function wp_json_encode($value) { return json_encode($value); }
function wp_generate_uuid4() { return bin2hex(random_bytes(16)); }
function wp_get_image_editor($file) { return $GLOBALS['migration_test_editor']; }
function wp_delete_file($file) { return unlink($file); }
function set_transient($key, $value, $expiry) { $GLOBALS['migration_test_transients'][$key] = $value; }
function delete_transient($key) { unset($GLOBALS['migration_test_transients'][$key]); }
function is_wp_error($value) { return $value instanceof WP_Error; }
function get_post($id) { return $GLOBALS['migration_test_posts'][$id] ?? null; }
function get_post_thumbnail_id($id) { return $id === 10 ? 101 : ($id === 20 ? 201 : 0); }
function get_post_meta($id, $key, $single) {
    if ($key === '_wp_attachment_backup_sizes') return $GLOBALS['migration_test_backups'][$id] ?? array();
    return $id === 10 ? (array('image_2' => 102, 'image_3' => 103)[$key] ?? 0) : 0;
}
function sanitize_title($title) {
    if (isset($GLOBALS['migration_test_sanitized_title'])) return $GLOBALS['migration_test_sanitized_title'];
    return trim(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title)), '-');
}
function sanitize_file_name($filename) { return str_replace('%', '', $filename); }
function wp_unique_filename($directory, $filename) {
    if (!empty($GLOBALS['migration_test_always_change_filename'])) return 'changed-' . $filename;
    $filename = sanitize_file_name($filename);
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
define('LOOPIS_LOG_DIR', $fixture . '/private-log');
require dirname(__DIR__, 3) . '/loopis-webmaster/includes/image-conversion/functions.php';
require dirname(__DIR__, 3) . '/loopis-webmaster/includes/image-cleanup/functions.php';
require dirname(__DIR__, 3) . '/loopis-webmaster/includes/image-conversion/batched-scan.php';
$GLOBALS['migration_test_dir'] = $fixture . '/uploads';
$wpdb = new Migration_Test_DB();
