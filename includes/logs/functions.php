<?php
/**
 * Resolve the WordPress debug log and root error_log paths and read recent entries.
 *
 * Reads are limited to the end of the file, with a 2 MiB maximum. Multiline
 * timestamped entries are kept together; other logs are read as lines.
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Return the expected path of the installation-root error_log. */
function loopis_develooper_error_log_path() {
    return rtrim(ABSPATH, '/\\') . '/error_log';
}

/** Resolve the configured WordPress debug log or installation-root error_log. */
function loopis_develooper_log_path($type) {
    if ('debug' === $type) {
        if (!defined('WP_DEBUG_LOG') || !WP_DEBUG_LOG) {
            return new WP_Error('debug_log_disabled', 'WordPress debug logging is not enabled.');
        }
        return is_string(WP_DEBUG_LOG) ? WP_DEBUG_LOG : WP_CONTENT_DIR . '/debug.log';
    }
    if ('error' === $type) {
        $path = loopis_develooper_error_log_path();
        if (!is_file($path)) {
            return new WP_Error('error_log_unavailable', 'No error_log file available in this WordPress installation');
        }
        return $path;
    }
    return new WP_Error('unknown_log', 'Unknown log type.');
}

/** Read the latest timestamped entries, or lines for logs without timestamps. */
function loopis_develooper_read_log($path, $limit = 100) {
    $handle = @fopen($path, 'rb');
    if (!$handle) {
        return new WP_Error('log_open_failed', 'The log file could not be opened: ' . $path);
    }
    if (!is_file($path) || fseek($handle, 0, SEEK_END) !== 0 || false === ($position = ftell($handle))) {
        fclose($handle);
        return new WP_Error('log_read_failed', 'The log file could not be read: ' . $path);
    }

    $buffer = '';
    $max_bytes = 2 * 1024 * 1024;
    while ($position > 0 && strlen($buffer) < $max_bytes) {
        $size = min(8192, $position, $max_bytes - strlen($buffer));
        $position -= $size;
        if (fseek($handle, $position) !== 0 || false === ($chunk = fread($handle, $size)) || strlen($chunk) !== $size) {
            fclose($handle);
            return new WP_Error('log_read_failed', 'The log file could not be read: ' . $path);
        }
        $buffer = $chunk . $buffer;
        if (preg_match_all('/^\\[\\d{1,4}[-\\/ ]/m', $buffer) > $limit) {
            break;
        }
    }
    $truncated = $position > 0 && strlen($buffer) >= $max_bytes;
    fclose($handle);

    if ($position > 0) {
        $newline = strpos($buffer, "\n");
        $buffer = false === $newline ? '' : substr($buffer, $newline + 1);
    }
    $buffer = rtrim($buffer, "\r\n");
    if ('' === $buffer) {
        return array('entries' => array(), 'truncated' => $truncated);
    }
    $matches = array();
    preg_match_all('/^\\[\\d{1,4}[-\\/ ]/m', $buffer, $matches, PREG_OFFSET_CAPTURE);
    $entries = array();
    if (!empty($matches[0])) {
        $offsets = array_column($matches[0], 1);
        foreach (array_slice($offsets, -$limit, null, true) as $index => $offset) {
            $end = $offsets[$index + 1] ?? strlen($buffer);
            $entries[] = rtrim(substr($buffer, $offset, $end - $offset), "\r\n");
        }
    } else {
        $entries = array_slice(preg_split('/\\r\\n|\\n|\\r/', $buffer), -$limit);
    }
    return array('entries' => $entries, 'truncated' => $truncated);
}
