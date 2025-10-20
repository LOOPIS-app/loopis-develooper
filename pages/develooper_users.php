<?php
/**
 * Develooper Users Page
 *
 * @package LOOPIS_Develooper
 * @subpackage Admin-page
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


require_once LOOPIS_DEV_DIR . 'functions/develooper_user_insert.php'; // Include user insert function
require_once LOOPIS_DEV_DIR . 'functions/db-reset/loopis_users_delete.php'; // Include user delete function
include_once(ABSPATH . 'wp-includes/pluggable.php'); // Include pluggable functions for user management

// Handle button click
$inserted_users = [];
$deleted_users = null;

if (isset($_POST['insert_sample_users'])) {
    $inserted_users = develooper_user_insert();
}

if (isset($_POST['delete_sample_users'])) {
    $deleted_users = loopis_users_delete();
} else {
    error_log("No delete_sample_users POST variable set.");
}


function develooper_users_page() {
    ?>
    <div class="wrap">
        <!-- Page title and description-->
        <h1>👥 Sample Users</h1>
        <p class="description">test</p>
        <?php
        /**$my_user = wp_get_current_user();
        echo '<div class="user-roles-display"><p>Current User: ' . esc_html($my_user->user_login) . ' (ID: ' . esc_html($my_user->ID) . ')</p></div>';
        */?>
        <p class="description">💡 Sample users for LOOPIS developers.</p>

        <!-- Page content-->
        <h2>Insert sample users</h2>
        <p><form method="POST"><button id="run_loopis_db_cleanup" class="button button-primary" type="submit" name="insert_sample_users">Insert</button></form><br>
        <i>[Greyed out if users are already inserted.]</i></p>

        <!-- HTML button -->

        <h2>Remove sample users</h2>
        <p><form method="POST"><button id="run_loopis_db_cleanup" class="button button-primary" type="submit" name="delete_sample_users">Remove</button><br>
        [Greyed out if no users are inserted.]</i></p>
    </div>
    <?php
}