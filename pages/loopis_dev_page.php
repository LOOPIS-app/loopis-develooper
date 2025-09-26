<?php
/**
 * WP Admin page for develooper tools.
 * 
 * Migrate if admin menu diversifies w/ submenus.
 * 
 * WARNING! The cleanup tool is intended for development purposes only.
 * Use with caution and only in a safe development environment!
 * 
 * @package LOOPIS_Dev
 * @subpackage Admin-page
 */

// Prevent direct access
if (!defined('ABSPATH')) { 
    exit; 
}

// Function to render the page
function loopis_dev_page() {
    ?>
    <div class="wrap">
        <!-- Page title and description-->
        <h1>🧑‍💻 LOOPIS Develoopers</h1>
        <p class="description">💡 Verktyg för utvecklare av LOOPIS.</p>

        <!-- Page content-->
        <h2>Hello tools.</h2>
        <p>Nothing so far...</p>
    </div>
<?php
}