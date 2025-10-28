<?php
/**
 * WP Admin page for configuring sample users.
 *
 * @package LOOPIS_Develooper
 * @subpackage Admin-page
 */

// Prevent direct access
if (!defined('ABSPATH')) { 
    exit; 
}

// Include functions
require_once LOOPIS_DEVELOOPER_DIR . 'functions/develooper_sample_users_insert.php';

// Include WP functions
<<<<<<< Updated upstream
require_once(ABSPATH . 'wp-includes/pluggable.php'); // neccesary for user management
=======
include_once(ABSPATH . 'wp-includes/pluggable.php'); // Include pluggable functions for user management
>>>>>>> Stashed changes

// Handle button click
$inserted_users = [];
$deleted_users = null;

if (isset($_POST['insert_sample_users'])) {
    $inserted_users = develooper_users_insert();
}

if (isset($_POST['delete_sample_users'])) {
    $deleted_users = loopis_users_delete();
}

// Function to render the page
function develooper_sample_users_page() {
    ?>
    <div class="wrap">
        <!-- Page title and description-->
        <h1>👥 Sample Users <span class="h1-right">Version <?php echo esc_html(LOOPIS_DEVELOOPER_VERSION); ?></span></h1>
<<<<<<< Updated upstream
        
=======
>>>>>>> Stashed changes
        <p class="description">💡 Configure sample users for testing during development.</p>

        <!-- Page content-->
        <h2>Configuration of sample users</h2>

<<<<<<< Updated upstream
        <p><i>[Add button to insert sample posts.]<br>
        <i>[Should be greyed out if no sample users are inserted.]</i></p>
=======
        <p><form method="POST">
            <button class="button button-primary" type="submit" name="insert_sample_users">Insert</button>
        </form></p>
        <p><i>[Fix: Greyed out if users are already inserted.]</i></p>
>>>>>>> Stashed changes

        <p><form method="POST">
            <button class="button button-primary" type="submit" name="reset_sample_users" disabled>Reset</button>
        </form></p>
        <p><i>[Function not yet created.]</i></p>

        <p><form method="POST">
            <button class="button button-primary" type="submit" name="delete_sample_users" disabled>Delete</button>
        </form></p>
        <p><i>[Fix: Greyed out if users are already inserted.]</i></p>
    </div>

    <?php
}