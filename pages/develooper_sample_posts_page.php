<?php
/**
 * WP Admin page for configuring sample posts.
 * 
 * @package LOOPIS_Dev
 * @subpackage Admin-page
 */

// Prevent direct access
if (!defined('ABSPATH')) { 
    exit; 
}

require_once LOOPIS_DEV_DIR . 'functions/develooper_sample_posts_insert.php'; // Include user insert function
// require_once LOOPIS_DEV_DIR . 'functions/develooper_posts_delete.php'; // Include user delete function, not yet created
//include_once(ABSPATH . 'wp-includes/pluggable.php'); // Include pluggable functions for post management

$inserted_post = [];

if (isset($_POST['insert_sample_posts'])) {
    $inserted_post = develooper_sample_posts_insert();
};
// Function to render the page
function develooper_sample_posts_page() {
    ?>
    <div class="wrap">
        <!-- Page title and description-->
        <h1>🎁 Sample Posts</h1>
        <p class="description">💡 Configure sample posts for testing during development.</p>

        <!-- Page content-->
        <h2>Insert sample posts</h2>
        <p><form method="POST">
            <button class="button button-primary" type="submit" name="insert_sample_posts">
                Insert
            </button>
        </form><i>[Add button to insert sample posts.]<br>
        [Greyed out if posts are already inserted.]</i></p>

        <h2>Reset sample posts</h2>
        <p><i>[Add button to reset sample posts.]<br>
        [Greyed out if no posts are inserted.]</i></p>
    </div>
<?php
}