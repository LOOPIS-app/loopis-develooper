<?php
/**
 * WP Admin page for develooper tools.
 * 
 * @package LOOPIS_Dev
 * @subpackage Admin-page
 */

// Prevent direct access
if (!defined('ABSPATH')) { 
    exit; 
}

// Include page functions
require_once LOOPIS_DEV_DIR . 'functions/develooper_roles.php';

// Function to render the page
function develooper_roles_page() {
    ?>
    <div class="wrap">
        <!-- Page title and description-->
        <h1>👥 User Roles</h1>
        <p class="description">💡 Current user roles and capabilities.</p>

        <!-- Page content - directly show the roles display -->
        <?php  echo '<div class="user-roles-display">';
    
    // Include CSS styles
    render_user_roles_styles();

    // Render LOOPIS capabilities
    render_loopis_capabilities_table();

    // Render WordPress roles and capabilities
    render_all_roles_table();
    
    echo '</div>'; ?>
    </div>
    <?php
}
