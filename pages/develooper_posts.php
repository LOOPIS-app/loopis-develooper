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

// Function to render the page
function develooper_posts_page() {
    ?>
    <div class="wrap">
        <!-- Page title and description-->
        <h1>🎁 Sample Posts</h1>
        <p class="description">💡 Sample posts for LOOPIS developers.</p>

        <!-- Page content-->
        <h2>Insert sample posts</h2>
        <p><i>[Add button to insert sample posts.]<br>
        [Greyed out if posts are already inserted.]</i></p>

        <h2>Reset sample posts</h2>
        <p><i>[Add button to reset sample posts.]<br>
        [Greyed out if no posts are inserted.]</i></p>
    </div>
<?php
}