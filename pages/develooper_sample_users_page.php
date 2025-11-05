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
require_once LOOPIS_DEVELOOPER_DIR . 'functions/develooper_sample_users_delete.php';

// Include WP functions
include_once(ABSPATH . 'wp-includes/pluggable.php'); // Include pluggable functions for user management

// Handle button click
$inserted_users = null;
$deleted_users = null;

if (isset($_POST['insert_sample_users'])) {
    $inserted_users = develooper_users_insert();

    // Store in transient for user insertion (the stored data will remain for 30s)
    set_transient('user_insertion_status_list', $inserted_users, 30);

    // Redirect to prevent form resubmission
    wp_redirect(add_query_arg('action', 'inserted', wp_get_referer()));
    exit;
}

if (isset($_POST['delete_sample_users'])) {
    $deleted_users = loopis_users_delete();

    // Store in transient for user deletion (the stored data will remain for 30s)
    set_transient('user_deletion_status_list', $deleted_users, 30);

    // Redirect to prevent form resubmission
    wp_redirect(add_query_arg('action', 'deleted', wp_get_referer()));
    exit;
}

// Function to render the page
function develooper_sample_users_page() {
    ?>
    <div class="wrap">
        <!-- Page title and description-->
        <h1>👥 Sample Users <span class="h1-right">Version <?php echo esc_html(LOOPIS_DEVELOOPER_VERSION); ?></span></h1>
        <p class="description">💡 Configure sample users for testing during development.</p>

        <?php
        // Show insert and delete users status messages
        if (isset($_GET['action'])) {
            // If insert is clicked
            if ($_GET['action'] === 'inserted') {
                // Fetch transient
                $inserted_users = get_transient('user_insertion_status_list');
                // If $inserted_users exists
                if ($inserted_users) {

                    //Show message display if user(s) is(are) inserted succesfully.
                    if (count($inserted_users['inserted']) > 0) {
                        $inserted_name = show_message_display($inserted_users['inserted']);
                        echo '<div class="notice notice-success is-dismissible"><p>✅ Sample users have been successfully inserted: ' . 
                             $inserted_name . '</p></div>';
                    }
                    //Show message display if user(s) is(are) already inserted.
                    if (count($inserted_users['existed']) > 0) {
                        $existed_name = show_message_display($inserted_users['existed']);
                        /*foreach ($inserted_users['existed'] as $user) {
                            $existed_name .= '<br>- ' . esc_html($user) . '';
                        }*/
                        echo '<div class="notice notice-info is-dismissible"><p>ℹ️ Sample users already exist: ' . 
                             $existed_name . '</p></div>';
                    }
                    //Show message display if user(s) is(are) failed to insert.
                    if (count($inserted_users['failed']) > 0) {
                        $failed_name = show_message_display($inserted_users['failed']);
                        echo '<div class="notice notice-error is-dismissible"><p>❌ Failed to insert sample users: ' . 
                             $failed_name . '</p></div>';
                    }
                // If $inserted_users does not exists    
                } else {
                    echo '<div class="notice notice-warning is-dismissible"><p>⚠️ No user insertion status found.</p></div>';
                }
                // delete transient 
                delete_transient('user_insertion_status_list');
            
                //echo '<div class="notice notice-success is-dismissible"><p>✅ Sample posts have been successfully inserted!</p></div>';
            } elseif ($_GET['action'] === 'deleted') {
                $deleted_users = get_transient('user_deletion_status_list');

                // If $deleted_users exists
                if ($deleted_users) {
                    // Show message display if user(s) is(are) deleted succesfully.
                    if (count($deleted_users['deleted']) > 0) {
                        $deleted_name = show_message_display($deleted_users['deleted']);

                        echo '<div class="notice notice-success is-dismissible"><p>✅ These sample users have been successfully deleted: ' .
                        $deleted_name . '</p></div>';
                    } 

                    // Show message display if user(s) is(are) already not existed.
                    if (count($deleted_users['nonexisted']) > 0) {
                        $nonexisted_name = show_message_display($deleted_users['nonexisted']);
                        echo '<div class="notice notice-info is-dismissible"><p>ℹ️ These sample users are not existed: ' .
                        $nonexisted_name . '</p></div>';
                    }
                // If $deleted_users does not exists
                } else {
                    echo '<div class="notice notice-warning is-dismissible"><p>⚠️ No user deletion status found.</p></div>';
                }

                delete_transient('user_deletion_status_list');
                //echo '<div class="notice notice-success is-dismissible"><p>❌ Sample users have been successfully deleted!</p></div>';
            }
        }
        ?>
        <!-- Page content-->
        <h2>Configuration of sample users</h2>

        <p><form method="POST">
            <button class="button button-primary" type="submit" name="insert_sample_users">Insert</button>
        </form></p>
        <p><i>[Fix: Greyed out if users are already inserted.]</i></p>

        <p><form method="POST">
            <button class="button button-primary" type="submit" name="reset_sample_users" disabled>Reset</button>
        </form></p>
        <p><i>[Function not yet created.]</i></p>

        <p><form method="POST">
            <button class="button button-primary" type="submit" name="delete_sample_users">Delete</button>
        </form></p>
        <p><i>[Fix: Greyed out if users are already inserted.]</i></p>
    </div>

    <?php
}

function show_message_display($obj_lists) {

    $list_str = '';
    foreach ($obj_lists as $lists) {
        $list_str .= '<br>- ' . esc_html($lists) . '';
    }
    return $list_str;
}