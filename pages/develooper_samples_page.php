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
if (isset($_POST['insert_samples'])) {
    develooper_users_insert();
    develooper_sample_posts_insert();
    // Redirect to prevent form resubmission
    wp_redirect(add_query_arg('action', 'samples_inserted', wp_get_referer()));
    exit;
}

if (isset($_POST['delete_samples'])) {
    develooper_sample_posts_delete();
    loopis_users_delete();
    // Redirect to prevent form resubmission
    wp_redirect(add_query_arg('action', 'samples_deleted', wp_get_referer()));
    exit;
}

// Function to render the page
function develooper_samples_page()
{
    ?>
    <div class="wrap">
        <!-- Page title and description-->
        <h1>♻ Develooper samples <span class="h1-right">Version <?php echo esc_html(LOOPIS_DEVELOOPER_VERSION); ?></span>
        </h1>
        <p class="description">💡 Configure sample content for testing and development.</p>

        <?php
        // Show success messages
        if (isset($_GET['action'])) {
            if ($_GET['action'] === 'samples_inserted') {
                echo '<div class="notice notice-success is-dismissible"><p>✅ Sample users and posts have been successfully inserted!</p></div>';
            } elseif ($_GET['action'] === 'samples_deleted') {
                echo '<div class="notice notice-success is-dismissible"><p>❌ Sample users and posts have been successfully deleted!</p></div>';
            }
        }
        ?>

        <!-- Page content-->
        <?php
        //insert_spacer(20);
        echo '<div class="coin" id="coin">';
        echo '<img src="' . LOOPIS_DEVELOOPER_URL . 'assets/img/coin.png" alt="Loading...">';
        echo '</div>';
        ?>

        <h2>🎁 Samples</h2>
        <p>Click the buttons to configure the sample user and posts.</p>

        <form method="POST" onsubmit="button_loading(this)">
            <button class="button button-primary wp-green loading-btn" type="submit" name="insert_samples">Insert</button>
            <button class="button button-primary wp-blue loading-btn" type="submit" name="reset_samples">Reset</button>
            <button class="button button-primary wp-red loading-btn" type="submit" name="delete_samples">Delete</button>
        </form>

    </div>
    <?php
}
