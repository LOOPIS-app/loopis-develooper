<?php
/**
 * Run from loopis-develooper with: php tests/loopis-develooper/log-viewer.php
 */

$fixture_dir = sys_get_temp_dir() . '/loopis-debug-widget-test-' . bin2hex(random_bytes(8));
if (!mkdir($fixture_dir, 0700)) {
    throw new RuntimeException('Could not create fixture directory.');
}
if (!mkdir($fixture_dir . '/wp-content', 0700)) {
    rmdir($fixture_dir);
    throw new RuntimeException('Could not create content fixture directory.');
}
define('ABSPATH', $fixture_dir . '/');
define('WP_CONTENT_DIR', $fixture_dir . '/wp-content');
define('WP_DEBUG_LOG', true);

class WP_Error {
    private $code;
    private $message;
    public function __construct($code, $message) { $this->code = $code; $this->message = $message; }
    public function get_error_code() { return $this->code; }
    public function get_error_message() { return $this->message; }
}

require dirname(__DIR__, 2) . '/includes/logs/functions.php';
require dirname(__DIR__, 2) . '/includes/logs/page.php';
require dirname(__DIR__, 2) . '/includes/logs/widget.php';

function is_super_admin() { return $GLOBALS['test_super_admin']; }
function current_user_can($capability) { return $capability === 'manage_options' && $GLOBALS['test_manage_options']; }
function wp_die($message) { throw new RuntimeException($message); }
function is_wp_error($value) { return $value instanceof WP_Error; }
function esc_html($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function esc_url($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function admin_url($path) { return 'https://example.test/area/wp-admin/' . $path; }
function wp_add_dashboard_widget(...$args) { $GLOBALS['test_widgets'][] = $args; }

function log_test_assert($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$file = tempnam(sys_get_temp_dir(), 'loopis-log-test-');
if (false === $file) {
    throw new RuntimeException('Could not create log fixture.');
}
try {
    log_test_assert(loopis_develooper_log_path('debug') === WP_CONTENT_DIR . '/debug.log', 'WordPress debug log path is incorrect.');
    $result = loopis_develooper_log_path('error');
    log_test_assert($result instanceof WP_Error && $result->get_error_message() === 'No error_log file available in this WordPress installation', 'Missing root error_log must be reported.');
    log_test_assert(loopis_develooper_log_path('unknown') instanceof WP_Error, 'Unknown log types must fail.');
    log_test_assert(loopis_develooper_read_log($file . '-missing') instanceof WP_Error, 'Missing logs must fail.');
    log_test_assert(loopis_develooper_read_log($file)['entries'] === array(), 'Empty logs must return no entries.');

    $contents = '';
    for ($i = 1; $i <= 120; $i++) {
        $contents .= "[12-Nov-2026 10:00:00 UTC] Entry $i\nStack trace $i\n";
    }
    file_put_contents($file, $contents);
    $result = loopis_develooper_read_log($file);
    log_test_assert(count($result['entries']) === 100 && $result['entries'][0] === "[12-Nov-2026 10:00:00 UTC] Entry 21\nStack trace 21"
        && end($result['entries']) === "[12-Nov-2026 10:00:00 UTC] Entry 120\nStack trace 120"
        && !$result['truncated'], 'The latest 100 multiline entries must be kept in order.');

    file_put_contents($file, implode("\n", range(1, 120)));
    $result = loopis_develooper_read_log($file);
    log_test_assert(count($result['entries']) === 100 && $result['entries'][0] === '21' && end($result['entries']) === '120', 'Logs without timestamps must show the latest 100 lines.');

    file_put_contents($file, "[12-Nov-2026 10:00:00 UTC] " . str_repeat('x', 2 * 1024 * 1024));
    $result = loopis_develooper_read_log($file);
    log_test_assert($result['truncated'], 'A log exceeding the read limit must report truncation.');

    foreach (array(array(false, true), array(true, false), array(false, false)) as $permissions) {
        list($GLOBALS['test_super_admin'], $GLOBALS['test_manage_options']) = $permissions;
        $GLOBALS['test_widgets'] = array();
        loopis_develooper_register_debug_dashboard_widget();
        log_test_assert($GLOBALS['test_widgets'] === array(), 'Unauthorized users must not receive the widget.');
        try {
            loopis_develooper_render_debug_log();
            throw new RuntimeException('Unauthorized log access was not denied.');
        } catch (RuntimeException $error) {
            log_test_assert($error->getMessage() === 'You are not allowed to view this log.', 'Unexpected authorization error.');
        }
        try {
            loopis_develooper_render_debug_dashboard_widget();
            throw new RuntimeException('Unauthorized dashboard access was not denied.');
        } catch (RuntimeException $error) {
            log_test_assert($error->getMessage() === 'You are not allowed to view this log.', 'Unexpected dashboard authorization error.');
        }
    }
    $GLOBALS['test_super_admin'] = true;
    $GLOBALS['test_manage_options'] = true;
    $debug_file = WP_CONTENT_DIR . '/debug.log';
    $error_file = ABSPATH . 'error_log';
    file_put_contents($debug_file, "[12-Nov-2026 10:00:00 UTC] debug-only\n");
    $previous = ini_get('error_log');
    ini_set('error_log', $debug_file);
    try {
        ob_start();
        loopis_develooper_render_error_log();
        $page = ob_get_clean();
        $summary = strpos($page, 'Latest 100 entries for this WordPress installation.');
        $path_position = strpos($page, '<strong>Log file:</strong> <code>' . esc_html($error_file) . '</code>');
        $notice = strpos($page, 'No error_log file available in this WordPress installation');
        log_test_assert($summary !== false && $path_position !== false && $notice !== false
            && $summary < $path_position && $path_position < $notice
            && strpos($page, 'debug-only') === false, 'Missing root error_log must show its expected path before the message, not the configured debug log.');

        file_put_contents($error_file, $contents . "[12-Nov-2026 10:00:00 UTC] <test>\n");
        clearstatcache(true, $error_file);
        log_test_assert(loopis_develooper_log_path('error') === $error_file, 'The error log must use the installation root.');
        ob_start();
        loopis_develooper_render_error_log();
        $page = ob_get_clean();
        log_test_assert(strpos($page, '<h1>error_log</h1>') !== false
            && strpos($page, '<code>' . esc_html($error_file) . '</code>') !== false && strpos($page, '&lt;test&gt;') !== false
            && strpos($page, '<test>') === false && strpos($page, 'debug-only') === false
            && substr_count($page, '[12-Nov-2026') === 100
            && strpos($page, 'Entry 21' . "\n") === false && strpos($page, 'Entry 22' . "\n") !== false, 'Error log page must display and escape the latest 100 entries from the root error_log.');
    } finally {
        ini_set('error_log', $previous);
    }

    unlink($debug_file);
    ob_start();
    loopis_develooper_render_debug_dashboard_widget();
    $widget = ob_get_clean();
    log_test_assert(strpos($widget, 'The log file could not be opened:') !== false, 'Missing debug log must display an error.');
    log_test_assert(strpos($widget, 'https://example.test/area/wp-admin/admin.php?page=loopis-debug-log') !== false && strpos($widget, 'Show more') !== false, 'The widget must link to the current site debug viewer.');

    file_put_contents($debug_file, $contents);
    ob_start();
    loopis_develooper_render_debug_log();
    $page = ob_get_clean();
    log_test_assert(strpos($page, '<h1>debug.log</h1>') !== false
        && strpos($page, 'Latest 100 entries for this WordPress installation.') !== false
        && substr_count($page, '[12-Nov-2026') === 100
        && strpos($page, 'Entry 20' . "\n") === false && strpos($page, 'Entry 21' . "\n") !== false, 'Debug log page must display the latest 100 entries.');
    file_put_contents($debug_file, '');
    ob_start();
    loopis_develooper_render_debug_dashboard_widget();
    $widget = ob_get_clean();
    log_test_assert(strpos($widget, 'No log entries found.') !== false, 'Empty debug log must be reported.');

    $contents = '';
    for ($i = 1; $i <= 12; $i++) {
        $contents .= "[12-Nov-2026 10:00:00 UTC] Entry $i <tag>\nStack trace $i\n";
    }
    file_put_contents($debug_file, $contents);
    ob_start();
    loopis_develooper_render_debug_dashboard_widget();
    $widget = ob_get_clean();
    log_test_assert(substr_count($widget, '[12-Nov-2026') === 10 && strpos($widget, 'Entry 2 &lt;tag&gt;') === false
        && strpos($widget, 'Entry 3 &lt;tag&gt;') !== false && strpos($widget, 'Entry 12 &lt;tag&gt;') !== false
        && strpos($widget, 'Stack trace 3') !== false && strpos($widget, '<tag>') === false, 'Widget must display and escape the latest 10 complete entries.');

    file_put_contents($debug_file, "[12-Nov-2026 10:00:00 UTC] " . str_repeat('x', 2 * 1024 * 1024));
    ob_start();
    loopis_develooper_render_debug_dashboard_widget();
    $widget = ob_get_clean();
    log_test_assert(strpos($widget, 'The log exceeds the read limit') !== false, 'Widget must warn when the debug log is truncated.');
    echo "Log viewer tests passed.\n";
} finally {
    unlink($file);
    if (is_file(WP_CONTENT_DIR . '/debug.log')) {
        unlink(WP_CONTENT_DIR . '/debug.log');
    }
    if (is_file(ABSPATH . 'error_log')) {
        unlink(ABSPATH . 'error_log');
    }
    rmdir(WP_CONTENT_DIR);
    rmdir($fixture_dir);
}
