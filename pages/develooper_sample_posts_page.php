<?php
/**
 * WP Admin page for configuring sample posts.
 * 
 * @package LOOPIS_Develooper
 * @subpackage Admin-page
 */

// Prevent direct access
if (!defined('ABSPATH')) { 
    exit; 
}

<<<<<<< Updated upstream
require_once LOOPIS_DEV_DIR . 'functions/develooper_sample_posts_insert.php'; // Include post insert function
require_once LOOPIS_DEV_DIR . 'functions/develooper_sample_posts_delete.php'; // Include post delete function

//include_once(ABSPATH . 'wp-includes/pluggable.php'); // Include pluggable functions for post management

$inserted_post = [];
$remove_post = null;
=======
// Include functions
require_once LOOPIS_DEVELOOPER_DIR . 'functions/develooper_sample_posts_insert.php';
require_once LOOPIS_DEVELOOPER_DIR . 'functions/develooper_sample_posts_delete.php';
>>>>>>> Stashed changes

// Handle form submissions
if (isset($_POST['insert_sample_posts'])) {
    develooper_sample_posts_insert();
    // Redirect to prevent form resubmission
    wp_redirect(add_query_arg('action', 'inserted', wp_get_referer()));
    exit;
}

if (isset($_POST['delete_sample_posts'])) {
    develooper_sample_posts_delete();
    // Redirect to prevent form resubmission
    wp_redirect(add_query_arg('action', 'deleted', wp_get_referer()));
    exit;
}

// Function to render the page
function develooper_sample_posts_page() {
    ?>
    <div class="wrap">
        <!-- Page title and description-->
        <h1>🎁 Sample Posts <span class="h1-right">Version <?php echo esc_html(LOOPIS_DEVELOOPER_VERSION); ?></span></h1>
        <p class="description">💡 Configure sample posts for testing during development.</p>

        <?php
        // Show success messages
        if (isset($_GET['action'])) {
            if ($_GET['action'] === 'inserted') {
                echo '<div class="notice notice-success is-dismissible"><p>✅ Sample posts have been successfully inserted!</p></div>';
            } elseif ($_GET['action'] === 'deleted') {
                echo '<div class="notice notice-success is-dismissible"><p>❌ Sample posts have been successfully deleted!</p></div>';
            }
        }
        ?>

        <!-- Page content-->
        <h2>Configuration of sample posts</h2>

        <p><form method="POST">
            <button class="button button-primary" type="submit" name="insert_sample_posts">Insert</button>
        </form></p>
        <p><i>[Fix: Greyed out if posts are already inserted.]</i></p>

        <p><form method="POST">
            <button class="button button-primary" type="submit" name="reset_sample_posts" disabled>Reset</button>
        </form></p>
        <p><i>[Function not yet created.]</i></p>

        <p><form method="POST">
            <button class="button button-primary" type="submit" name="delete_sample_posts">Delete</button>
        </form></p>
        <p><i>[Fix: Greyed out if posts are already inserted.]</i></p>
    </div>
    <?php
}