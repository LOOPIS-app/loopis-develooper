<?php
/** Test default and configured image log paths in isolated PHP processes. */

$fixture = sys_get_temp_dir() . '/loopis-log-test-' . bin2hex(random_bytes(8));
mkdir($fixture, 0700);
$fixture = realpath($fixture);
mkdir($fixture . '/wordpress', 0700);
mkdir($fixture . '/loopis-image-migration-logs', 0700);
file_put_contents($fixture . '/loopis-image-migration-logs/site-1-conversion.jsonl', "previous activity\n");
define('ABSPATH', $fixture . '/wordpress/');
$_SERVER['DOCUMENT_ROOT'] = ABSPATH;
if (($argv[1] ?? '') === 'custom') {
    define('LOOPIS_LOG_DIR', $fixture . '/custom-logs');
}

class WP_Error {
    public $message;
    public function __construct($code, $message) { $this->message = $message; }
}
function add_action() {}
function wp_normalize_path($path) { return str_replace('\\', '/', $path); }
function trailingslashit($path) { return rtrim($path, '/\\') . '/'; }
function wp_mkdir_p($path) { return is_dir($path) || mkdir($path, 0777, true); }
function get_current_blog_id() { return $GLOBALS['site_id']; }
function check_log($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

require __DIR__ . '/../../../loopis-webmaster/includes/image-conversion/functions.php';

$root = $fixture . (defined('LOOPIS_LOG_DIR') ? '/custom-logs' : '/loopis-logs');
$files = array();
try {
    foreach (array(1, 2) as $site) {
        $GLOBALS['site_id'] = $site;
        foreach (array('conversion' => 'migration', 'cleanup' => 'cleanup') as $tool => $name) {
            $expected = $root . '/image-' . $name . '-site-' . $site . '.jsonl';
            $actual = loopis_image_conversion_log_file($tool);
            if (is_string($actual)) {
                $files[] = $actual;
            }
            check_log($actual === $expected, 'Unexpected log path: ' . ($actual instanceof WP_Error ? $actual->message : $actual));
            check_log(loopis_image_conversion_log_file($tool) === $actual, 'Repeated calls must use the same log.');
            check_log((fileperms($actual) & 0777) === 0600, 'Logs must be private.');
        }
    }
    check_log((fileperms($root) & 0777) === 0700, 'The log directory must be private.');
    check_log(file_get_contents($fixture . '/loopis-image-migration-logs/site-1-conversion.jsonl') === "previous activity\n", 'Previous logs must remain untouched.');
    check_log(filesize($files[0]) === 0, 'New logs must start empty.');
    echo "Image log path tests passed.\n";
} finally {
    foreach ($files as $file) {
        unlink($file);
    }
    if (is_dir($root)) {
        rmdir($root);
    }
    unlink($fixture . '/loopis-image-migration-logs/site-1-conversion.jsonl');
    rmdir($fixture . '/loopis-image-migration-logs');
    rmdir($fixture . '/wordpress');
    rmdir($fixture);
}
