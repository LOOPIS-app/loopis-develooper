<?php
/** Run from loopis-develooper with: php tests/loopis-develooper/roles.php */

define('ABSPATH', __DIR__ . '/');
$GLOBALS['roles_test_allowed'] = true;
$GLOBALS['roles_test_roles'] = (object) array(
    'roles' => array(
        'administrator' => array('name' => 'Administrator', 'capabilities' => array('admin_secret' => true)),
        'develooper' => array('name' => 'Develooper', 'capabilities' => array('dev_secret' => true)),
        'editor' => array('name' => 'Editor <custom>', 'capabilities' => array('write_posts' => true, 'denied_cap' => false, 'read' => true, 'cap<script>' => true)),
        'empty-role' => array('name' => 'Empty role', 'capabilities' => array('not_granted' => false)),
    ),
);
function wp_roles() { return $GLOBALS['roles_test_roles']; }
function translate_user_role($name) { return $name; }
function current_user_can($capability) { return $capability === 'manage_options' && $GLOBALS['roles_test_allowed']; }
function wp_die($message) { throw new RuntimeException($message); }
function esc_html($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }

function roles_test_assert($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

require dirname(__DIR__, 2) . '/includes/roles/functions.php';
require dirname(__DIR__, 2) . '/includes/roles/page.php';

$original_roles = $GLOBALS['roles_test_roles']->roles;
$roles = loopis_develooper_get_roles();
roles_test_assert(array_keys($roles) === array('editor', 'empty-role'), 'Hidden roles must not be returned.');
roles_test_assert($roles['editor']['capabilities'] === array('cap<script>', 'read', 'write_posts'), 'Only granted capabilities must be returned in alphabetical order.');
roles_test_assert($roles['empty-role']['capabilities'] === array() && $GLOBALS['roles_test_roles']->roles === $original_roles, 'Empty roles must remain visible and role data must not be modified.');

ob_start();
develooper_roles_page();
$page = ob_get_clean();
roles_test_assert(strpos($page, 'widefat striped') !== false && strpos($page, 'Editor &lt;custom&gt;') !== false
    && strpos($page, '<h1>Roles</h1>') !== false && strpos($page, '<th scope="col">Slug</th>') !== false
    && strpos($page, '<td><code>editor</code></td>') !== false
    && strpos($page, '<th scope="row" style="vertical-align: top;">') !== false
    && strpos($page, '<ul style="margin-top: 0;">') !== false
    && strpos($page, 'cap&lt;script&gt;') !== false && strpos($page, '<script>') === false
    && strpos($page, 'admin_secret') === false && strpos($page, 'dev_secret') === false
    && strpos($page, 'denied_cap') === false && strpos($page, 'No capabilities granted.') !== false, 'The role table must display and escape only visible roles and granted capabilities.');

$GLOBALS['roles_test_roles']->roles = array('administrator' => $original_roles['administrator'], 'develooper' => $original_roles['develooper']);
ob_start();
develooper_roles_page();
$page = ob_get_clean();
roles_test_assert(strpos($page, 'No roles to display.') !== false && strpos($page, '<table') === false, 'An empty visible role list must be reported.');

$GLOBALS['roles_test_allowed'] = false;
try {
    develooper_roles_page();
    throw new RuntimeException('Unauthorized roles page access was not denied.');
} catch (RuntimeException $error) {
    roles_test_assert($error->getMessage() === 'You do not have permission to access this page.', 'Unexpected authorization error.');
}

echo "Role overview tests passed.\n";
