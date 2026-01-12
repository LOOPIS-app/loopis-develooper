<?php
/**
 * WP Admin page for configuring sample content.
 * 
 * @package LOOPIS_Develooper
 * @subpackage Sample-content
 */

// Prevent direct access
if (!defined('ABSPATH')) { 
    exit; 
}

// Include functions
require_once LOOPIS_DEVELOOPER_DIR . 'functions/develooper_sample_posts_insert.php';
require_once LOOPIS_DEVELOOPER_DIR . 'functions/develooper_sample_posts_delete.php';
require_once LOOPIS_DEVELOOPER_DIR . 'functions/develooper_sample_users_insert.php';
require_once LOOPIS_DEVELOOPER_DIR . 'functions/develooper_sample_users_delete.php';

// Include WP functions
include_once(ABSPATH . 'wp-includes/pluggable.php'); // Included for user management

// Handle form submissions
if (isset($_POST['insert_sample_posts'])) {
    develooper_sample_posts_insert();
    // Redirect to prevent form resubmission
    wp_redirect(add_query_arg('action', 'posts_inserted', wp_get_referer()));
    exit;
}

if (isset($_POST['delete_sample_posts'])) {
    develooper_sample_posts_delete();
    // Redirect to prevent form resubmission
    wp_redirect(add_query_arg('action', 'posts_deleted', wp_get_referer()));
    exit;
}

if (isset($_POST['insert_sample_users'])) {
    develooper_users_insert();
    // Redirect to prevent form resubmission
    wp_redirect(add_query_arg('action', 'users_inserted', wp_get_referer()));
    exit;
}

if (isset($_POST['delete_sample_users'])) {
    loopis_users_delete();
    // Redirect to prevent form resubmission
    wp_redirect(add_query_arg('action', 'users_deleted', wp_get_referer()));
    exit;
}

// Function to render the page
function develooper_samples_page() {
    ?>
    <div class="wrap">
        <!-- Page title and description-->
        <h1>♻ Develooper samples <span class="h1-right">Version <?php echo esc_html(LOOPIS_DEVELOOPER_VERSION); ?></span></h1>
        <p class="description">💡 Configure sample content for testing and development.</p>

        <?php
        // Show success messages
        if (isset($_GET['action'])) {
            if ($_GET['action'] === 'posts_inserted') {
                echo '<div class="notice notice-success is-dismissible"><p>✅ Sample posts have been successfully inserted!</p></div>';
            } elseif ($_GET['action'] === 'posts_deleted') {
                echo '<div class="notice notice-success is-dismissible"><p>❌ Sample posts have been successfully deleted!</p></div>';
            } elseif ($_GET['action'] === 'users_inserted') {
                echo '<div class="notice notice-success is-dismissible"><p>✅ Sample users have been successfully inserted!</p></div>';
            } elseif ($_GET['action'] === 'users_deleted') {
                echo '<div class="notice notice-success is-dismissible"><p>❌ Sample users have been successfully deleted!</p></div>';
            }
        }
        ?>

        <!-- Page content-->
        <?php insert_spacer_admin(20) ?>
        
        <h2>🎁 Sample posts</h2>
        <p>Click the buttons to configure the sample posts.</p>

        <form method="POST">
            <button class="button button-primary wp-green" type="submit" name="insert_sample_posts">Insert</button>
            <button class="button button-primary wp-blue" type="submit" name="reset_sample_posts">Reset</button>
            <button class="button button-primary wp-red" type="submit" name="delete_sample_posts">Delete</button>
        </form>

        <p><i>[Fix: Grey out insert/delete depending on if posts are already inserted.]</i></p>

        <?php insert_spacer_admin(20) ?>
        
        <h2>👥 Sample users</h2>
        <p>Click the buttons to configure the sample users.</p>

        <p><form method="POST">
            <button class="button button-primary wp-green" type="submit" name="insert_sample_users">Insert</button>
            <button class="button button-primary wp-blue" type="submit" name="reset_sample_users">Reset</button>
            <button class="button button-primary wp-red" type="submit" name="delete_sample_users">Delete</button>
        </form></p>
        
        <p><i>[Fix: Grey out insert/delete depending on if users are already inserted.]</i></p>

    </div>
    <?php
}
