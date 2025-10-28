<?php
/**
 * Functions for listing user roles and capabilities.
 * 
 * This file is included from the WP admin page with the same name.
 * 
 * @package LOOPIS_Develooper
 * @subpackage Dev-tools
 */

// Prevent direct access
if (!defined('ABSPATH')) { 
    exit; 
}

/**
 * Render CSS styles for the tables
 */
function render_user_roles_styles() {
    ?>
    <style>
        /* Custom additions to WP Admin styles */
        .role-name {
            font-family: Monaco, Consolas, "Andale Mono", "DejaVu Sans Mono", monospace;
            font-size: 0.9em;
        }

        .cap-list {
            border: 1px solid #dcdcde;
            border-left: 3px solid #0073aa;
            padding: 0.25rem 0.75rem;
            margin: 0.5rem 0;
            max-height: 200px;
            max-width: 300px;
            overflow-y: auto;
            border-radius: 0 3px 3px 0;
        }
        
        .cap-item {
            margin: 0.25rem 0;
            font-family: Monaco, Consolas, "Andale Mono", "DejaVu Sans Mono", monospace;
            font-size: 0.7rem;
            line-height: 1.4;
        }
        
        .cap-granted { 
            color: #00a32a; 
        }
        
        .cap-denied { 
            color: #d63638; 
        }
        
        .capability-header {
            text-align: center !important;
        }
        
        .capability-status {
            display: block;
            text-align: center;
        }
    </style>
    <?php
}


/**
 * Render table for LOOPIS capabilities
 */
function render_loopis_capabilities_table() {
    $roles = wp_roles()->get_names();
    $loopis_capabilities = [
        'loopis_admin' => 'Admin Access',
        'loopis_support' => 'Support Access', 
        'loopis_economy' => 'Economy Access',
        'loopis_storage_book' => 'Storage Booking',
        'loopis_storage_fetch' => 'Storage Fetching'
    ];
    
    echo '<div class="roles-section">';
    echo '<h3>🐙 LOOPIS capabilities</h3>';
    
    echo '<table class="wp-list-table widefat fixed striped loopis-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th scope="col">Role Key</th>';
    
    foreach ($loopis_capabilities as $cap => $label) {
        echo '<th scope="col" class="capability-header">' . esc_html($label) . '</th>';
    }
    
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($roles as $role_key => $role_name) {
        $role = get_role($role_key);
        if (!$role) continue;
        
        echo '<tr>';
        echo '<td><span class="role-name">' . esc_html($role_key) . '</span></td>';
        
        foreach ($loopis_capabilities as $cap => $label) {
            $has_capability = isset($role->capabilities[$cap]) && $role->capabilities[$cap];
            $status = $has_capability ? '✅' : '❌';
            $class = $has_capability ? 'cap-granted' : 'cap-denied';
            
            echo '<td><span class="capability-status ' . $class . '">' . $status . '</span></td>';
        }
        
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

/**
 * Render table for WordPress roles and capabilities
 */
function render_all_roles_table() {
    $roles = wp_roles()->get_names();
    
    echo '<div class="roles-section">';
    echo '<h2><span class="dashicons dashicons-wordpress" style="font-size: 1.2em; vertical-align: middle; margin-right: 0.25rem;"></span> WordPress capabilities</h2>';
    
    echo '<table class="wp-list-table widefat fixed striped roles-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th scope="col" style="width: 15%;">Role Key</th>';
    echo '<th scope="col" style="width: 20%;">Display Name</th>';
    echo '<th scope="col" style="width: 65%;">Capabilities</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($roles as $role_key => $role_name) {
        $role = get_role($role_key);
        if (!$role) continue;
        
        echo '<tr>';
        echo '<td><span class="role-name">' . esc_html($role_key) . '</span></td>';
        echo '<td><strong>' . esc_html($role_name) . '</strong></td>';
        echo '<td>';
        
        render_role_capabilities($role);
        
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

/**
 * Render capabilities for a specific role
 */
function render_role_capabilities($role) {
    $capabilities = $role->capabilities;
    ksort($capabilities);
    
    $granted_caps = array_filter($capabilities, function($v) { return $v === true; });
    $denied_caps = array_filter($capabilities, function($v) { return $v === false; });
    
    // Show granted capabilities
    echo '<div class="capabilities-section">';
    echo '<strong>✅ Active Capabilities (' . count($granted_caps) . '):</strong>';
    echo '<div class="cap-list">';
    
    if (!empty($granted_caps)) {
        foreach ($granted_caps as $cap => $granted) {
            echo '<div class="cap-item cap-granted">• ' . esc_html($cap) . '</div>';
        }
    } else {
        echo '<div class="cap-item">No active capabilities</div>';
    }
    
    echo '</div>';
    echo '</div>';
    
    // Show denied capabilities (if any)
    if (!empty($denied_caps)) {
        echo '<div class="capabilities-section">';
        echo '<strong>❌ Denied Capabilities (' . count($denied_caps) . '):</strong>';
        echo '<div class="cap-list">';
        
        foreach ($denied_caps as $cap => $granted) {
            echo '<div class="cap-item cap-denied">• ' . esc_html($cap) . '</div>';
        }
        
        echo '</div>';
        echo '</div>';
    }
}