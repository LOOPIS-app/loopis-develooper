<?php
/**
 * Render the installation-wide log pages.
 *
 * Only super administrators with manage_options can view these logs, which
 * may contain entries from any site in the network.
 */

if (!defined('ABSPATH')) {
    exit;
}

function loopis_develooper_render_debug_log() {
    loopis_develooper_render_log('debug', 'debug.log');
}

function loopis_develooper_render_error_log() {
    loopis_develooper_render_log('error', 'error_log');
}

/** Display the installation-wide log only to authorized super administrators. */
function loopis_develooper_render_log($type, $title) {
    if (!is_super_admin() || !current_user_can('manage_options')) {
        wp_die('You are not allowed to view this log.');
    }
    $path = loopis_develooper_log_path($type);
    $result = is_wp_error($path) ? $path : loopis_develooper_read_log($path);
    $display_path = is_wp_error($path) && $path->get_error_code() === 'error_log_unavailable'
        ? loopis_develooper_error_log_path()
        : $path;
    ?>
    <div class="wrap">
        <h1><?php echo esc_html($title); ?></h1>
        <p>Latest 100 entries for this WordPress installation.</p>
        <?php if (!is_wp_error($display_path)) : ?>
            <p><strong>Log file:</strong> <code><?php echo esc_html($display_path); ?></code></p>
        <?php endif; ?>
        <?php if (is_wp_error($result)) : ?>
            <div class="notice notice-error"><p><?php echo esc_html($result->get_error_message()); ?></p></div>
        <?php elseif (empty($result['entries'])) : ?>
            <p>No log entries found.</p>
        <?php else : ?>
            <pre style="white-space: pre-wrap; overflow-wrap: anywhere;"><?php echo esc_html(implode("\n", $result['entries'])); ?></pre>
        <?php endif; ?>
        <?php if (!is_wp_error($result) && $result['truncated']) : ?>
            <div class="notice notice-warning"><p>The log exceeds the read limit; some entries may be incomplete or omitted.</p></div>
        <?php endif; ?>
    </div>
    <?php
}
