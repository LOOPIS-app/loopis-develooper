<?php
/**
 * Register and render the debug log dashboard widget for authorized super administrators.
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Add the debug log widget to the current site's dashboard. */
function loopis_develooper_register_debug_dashboard_widget() {
    if (!is_super_admin() || !current_user_can('manage_options')) {
        return;
    }

    wp_add_dashboard_widget('loopis_debug_log', 'debug.log', 'loopis_develooper_render_debug_dashboard_widget');
}

/** Display the latest ten debug entries and link to the full log viewer. */
function loopis_develooper_render_debug_dashboard_widget() {
    if (!is_super_admin() || !current_user_can('manage_options')) {
        wp_die('You are not allowed to view this log.');
    }

    $path = loopis_develooper_log_path('debug');
    $result = is_wp_error($path) ? $path : loopis_develooper_read_log($path, 10);
    ?>
    <p>Latest 10 entries for this WP installation. → <a href="<?php echo esc_url(admin_url('admin.php?page=loopis-debug-log')); ?>">Show more</a></p>
    <?php if (is_wp_error($result)) : ?>
        <p><?php echo esc_html($result->get_error_message()); ?></p>
    <?php elseif (empty($result['entries'])) : ?>
        <p>No log entries found.</p>
    <?php else : ?>
        <pre style="white-space: pre-wrap; overflow-wrap: anywhere; max-height: 300px; overflow-y: auto;"><?php echo esc_html(implode("\n", $result['entries'])); ?></pre>
    <?php endif; ?>
    <?php if (!is_wp_error($result) && $result['truncated']) : ?>
        <p>The log exceeds the read limit; some entries may be incomplete or omitted.</p>
    <?php endif; ?>
    <?php
}
