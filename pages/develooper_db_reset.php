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

// Include page functions
require_once LOOPIS_DEV_DIR . 'functions/develooper_db_reset.php';

// Function to render the page
function develooper_db_reset_page() {
    ?>
    <div class="wrap">
        <!-- Page title and description-->
        <h1>🧑‍💻 Database Reset</h1>
        <p class="description"><strong>⚠ WARNING!</strong> Only for testing in development environment.</p>

        <!-- Page content-->
        <p>
            <button id="run_loopis_db_cleanup" class="button button-primary" value="Återställ">Reset</button>
        </p>
        <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column">Component</th>
                        <th scope="col" class="manage-column">Location</th>
                        <th scope="col" class="manage-column">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="column-component">LOOPIS tables</td>
                        <td class="column-place">wp_loopis_lockers/settings</td>
                        <td class="column-status" data-step="databas"><span class="status"><?php echo loopis_sp_get_step_status('databas'); ?></span></td>
                    </tr>
                    <tr>
                        <td class="column-component">LOOPIS pages</td>
                        <td class="column-place">wp_posts</td>
                        <td class="column-status" data-step="pages"><span class="status"><?php echo loopis_sp_get_step_status('pages'); ?></span></td>
                    </tr>
                    <tr>
                        <td class="column-component">LOOPIS categories</td>
                        <td class="column-place">wp_terms</td>
                        <td class="column-status" data-step="categories"><span class="status"><?php echo loopis_sp_get_step_status('categories'); ?></span></td>
                    </tr>
                    <tr>
                        <td class="column-component">LOOPIS tags</td>
                        <td class="column-place">wp_terms</td>
                        <td class="column-status" data-step="tags"><span class="status"><?php echo loopis_sp_get_step_status('tags'); ?></span></td>
                    </tr>
                    <tr>
                        <td class="column-component">LOOPIS user roles</td>
                        <td class="column-place">wp_user_roles</td>
                        <td class="column-status" data-step="user_roles"><span class="status"><?php echo loopis_sp_get_step_status('user_roles'); ?></span></td>
                    </tr>
                    <tr>
                        <td class="column-component">LOOPIS users</td>
                        <td class="column-place">wp_users</td>
                        <td class="column-status" data-step="users"><span class="status"><?php echo loopis_sp_get_step_status('users'); ?></span></td>
                    </tr>
                    <tr>
                        <td class="column-component">LOOPIS plugins</td>
                        <td class="column-place">wp_plugins</td>
                        <td class="column-status" data-step="plugins"><span class="status"><?php echo loopis_sp_get_step_status('plugins'); ?></span></td>
                    </tr>
                </tbody>
        </table></p>
    </div>
<?php
}