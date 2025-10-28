<?php
/**
 * WP Admin page for viewing current user roles.
 * 
 * @package LOOPIS_Develooper
 * @subpackage Admin-page
 */

// Prevent direct access
if (!defined('ABSPATH')) { 
    exit; 
}

// Include functions
require_once LOOPIS_DEVELOOPER_DIR . 'functions/develooper_roles_output.php';

// Function to render the page
function develooper_roles_page() {
    ?>
    <div class="wrap">
        <!-- Page title and description-->
        <h1>👥 User Roles <span class="h1-right">Version <?php echo esc_html(LOOPIS_DEVELOOPER_VERSION); ?></span></h1>
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
