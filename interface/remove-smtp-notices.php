<?php
/**
 * Remove notices from Post SMTP
 * 
 * @package LOOPIS Develooper
 */

// Prevent direct access
if (!defined('ABSPATH')) { 
    exit; 
}

// Remove Freemius notices from Post SMTP and other plugins
add_action('admin_head', 'loopis_remove_freemius_notices', 999);

function loopis_remove_freemius_notices() {
    global $wp_filter;
    
    // Remove Freemius admin notices by unsetting specific callbacks
    if (isset($wp_filter['admin_notices'])) {
        foreach ($wp_filter['admin_notices']->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $key => $callback) {
                // Check if it's a Freemius notice
                if (is_array($callback['function']) && is_object($callback['function'][0])) {
                    $class_name = get_class($callback['function'][0]);
                    if (strpos($class_name, 'Freemius') !== false || strpos($class_name, 'FS_') !== false) {
                        unset($wp_filter['admin_notices']->callbacks[$priority][$key]);
                    }
                }
            }
        }
    }
}
