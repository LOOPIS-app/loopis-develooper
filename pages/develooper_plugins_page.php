<?php
/**
 * WP Admin page for configuring developer plugins.
 * 
 * @package LOOPIS_Develooper
 * @subpackage Admin-page
 */

// Prevent direct access
if (!defined('ABSPATH')) { 
    exit; 
}

// Function to render the page
function develooper_plugins_page() {
    ?>
    <div class="wrap">
        <!-- Page title and description-->
        <h1>🧩 Plugins <span class="h1-right">Version <?php echo esc_html(LOOPIS_DEVELOOPER_VERSION); ?></span></h1>
        <p class="description">💡 Useful plugins for LOOPIS developers.</p>

        <!-- Page content-->
        <h2>Recommended plugins</h2>
        <p><i>[Add list of recommended plugins + button to install/update.]</i></p>

        <h2>Installed plugins</h2>
        <p><i>[Add list of recommended plugins installed + button to delete.]</i></p>
    </div>
<?php
}