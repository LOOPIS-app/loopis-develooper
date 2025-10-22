<?php
/**
 * WP Admin page for configuring sample users.
 *
 * @package LOOPIS_Develooper
 * @subpackage Admin-page
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once LOOPIS_DEV_DIR . 'functions/develooper_sample_users_insert.php'; // Include user insert function
// require_once LOOPIS_DEV_DIR . 'functions/develooper_users_delete.php'; // Include user delete function, not yet created
include_once(ABSPATH . 'wp-includes/pluggable.php'); // Include pluggable functions for user management

// Handle button click
$inserted_users = [];
$deleted_users = null;

if (isset($_POST['insert_sample_users'])) {
    $inserted_users = develooper_users_insert();
}

if (isset($_POST['delete_sample_users'])) {
    $deleted_users = loopis_users_delete();
}


function develooper_sample_users_page() {
    ?>
    <div class="wrap">
        <!-- Page title and description-->
        <h1>👥 Sample Users</h1>
        
        <p class="description">💡 Configure sample users for testing during development.</p>

        <!-- Page content-->
        <h2>Configure sample users</h2>
        <p><form method="POST"><button class="button button-primary" type="submit" name="insert_sample_users">Insert</button></form>
        <i>[Should be greyed out if sample users are already inserted.]</i></p>
        <i>[Add confirmation when completed: X users inserted.]</i></p>

        <p><form method="POST"><button class="button button-primary" type="submit" name="reset_sample_users">Reset</button></form>
        <i>[Should be greyed out if no sample users are inserted.]</i></p>

        <p><form method="POST"><button class="button button-primary" type="submit" name="delete_sample_users">Delete</button></form>
        <i>[Should be greyed out if no sample users are inserted.]</i></p>
    </div>

    <?php
}