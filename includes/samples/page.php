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

// Include WP functions
include_once(ABSPATH . 'wp-includes/pluggable.php'); // Included for user management

// Handle form submissions
if (isset($_POST['insert_samples'])) {
    $users_result = develooper_users_insert();
    $posts_result = develooper_sample_posts_insert();
    // Redirect to prevent form resubmission
    if (!empty($users_result['failed']) && is_wp_error($posts_result)) {
        $result_action = 'samples_users_and_posts_failed';
    } elseif (!empty($users_result['failed'])) {
        $result_action = 'samples_users_failed';
    } else {
        $result_action = is_wp_error($posts_result) ? 'samples_posts_failed' : 'samples_inserted';
    }
    wp_redirect(add_query_arg('action', $result_action, wp_get_referer()));
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
        <h1>Samples</h1>
        <p>Click the buttons to configure samples for testing and development purposes.</p>

        <?php
        // Show success messages
        if (isset($_GET['action'])) {
            if ($_GET['action'] === 'samples_inserted') {
                echo '<div class="notice notice-success is-dismissible"><p>Sample users and posts have been successfully inserted!</p></div>';
            } elseif ($_GET['action'] === 'samples_users_failed') {
                echo '<div class="notice notice-error is-dismissible"><p>Some sample users could not be created or added to the required sites. A user may already have been created; check the log before retrying.</p></div>';
            } elseif ($_GET['action'] === 'samples_posts_failed') {
                echo '<div class="notice notice-error is-dismissible"><p>Some sample post images could not be reused. A post may already have been created; check the log before retrying.</p></div>';
            } elseif ($_GET['action'] === 'samples_users_and_posts_failed') {
                echo '<div class="notice notice-error is-dismissible"><p>Some sample users could not be created or added to the required sites, and some sample post images could not be reused. Users or posts may already have been created; check the log before retrying.</p></div>';
            } elseif ($_GET['action'] === 'samples_deleted') {
                echo '<div class="notice notice-success is-dismissible"><p>Sample users and posts have been successfully deleted!</p></div>';
            }
        }
        ?>

        <form method="post">
            <button class="button button-primary" type="submit" name="insert_samples">Insert</button>
            <button class="button button-secondary" type="submit" name="reset_samples">Reset</button>
            <button class="button" type="submit" name="delete_samples">Delete</button>
        </form>

        <h2>Sample users</h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th scope="col" style="width: 100px; max-width: 100px; box-sizing: border-box;">Status</th>
                    <th scope="col">Name</th>
                    <th scope="col">Roles</th>
                    <th scope="col">Posts</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (get_sample_users() as $user) : ?>
                    <?php
                    $existing_user = get_user_by('login', $user['user_login']);
                    $is_member = $existing_user && is_user_member_of_blog($existing_user->ID, get_current_blog_id());
                    $count = $existing_user ? count_user_posts($existing_user->ID, 'post', true) : 0;
                    if (!is_numeric($count)) {
                        error_log('LOOPIS Develooper: Could not count published posts for sample user ' . $user['user_login']);
                    }
                    ?>
                    <tr>
                        <td>
                            <span aria-hidden="true"><?php echo $is_member ? '✅' : '💢'; ?></span>
                            <span class="screen-reader-text"><?php echo $is_member ? 'Exists on this site' : 'Missing from this site'; ?></span>
                        </td>
                        <td><?php echo esc_html(trim($user['first_name'] . ' ' . $user['last_name'])); ?></td>
                        <td><?php if ($is_member && !empty($existing_user->roles)) : ?><?php foreach ($existing_user->roles as $index => $role) : ?><?php if ($index > 0) : ?>, <?php endif; ?><code><?php echo esc_html($role); ?></code><?php endforeach; ?><?php else : ?>None<?php endif; ?></td>
                        <td><?php echo is_numeric($count) ? esc_html((string) $count) : 'Count unavailable'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="description">Samples are defined in the folder "content" within the plugin directory.</p>
    </div>
    <?php
}
