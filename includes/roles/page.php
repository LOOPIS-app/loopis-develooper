<?php
/**
 * Page for listing all user roles and capabilities.
 */

// Prevent direct access
if (!defined('ABSPATH')) { 
    exit; 
}

/** Render the current site's roles and granted capabilities. */
function develooper_roles_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to access this page.');
    }

    $roles = loopis_develooper_get_roles();
    ?>
    <div class="wrap">
        <h1>Roles</h1>
        <p>Granted capabilities for roles on this site. Administrator and develooper roles are excluded.</p>
        <?php if (empty($roles)) : ?>
            <p>No roles to display.</p>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th scope="col">Role</th>
                        <th scope="col">Slug</th>
                        <th scope="col">Granted capabilities</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $slug => $role) : ?>
                        <tr>
                            <th scope="row" style="vertical-align: top;">
                                <strong><?php echo esc_html($role['name']); ?></strong>
                            </th>
                            <td><code><?php echo esc_html($slug); ?></code></td>
                            <td>
                                <?php if (empty($role['capabilities'])) : ?>
                                    No capabilities granted.
                                <?php else : ?>
                                    <ul style="margin-top: 0;">
                                        <?php foreach ($role['capabilities'] as $capability) : ?>
                                            <li><code><?php echo esc_html($capability); ?></code></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}
