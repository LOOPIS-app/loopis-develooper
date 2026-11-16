<?php
/** Run from loopis-develooper with: php tests/loopis-develooper/sample-users.php */

$fixture = sys_get_temp_dir() . '/loopis-sample-users-test-' . bin2hex(random_bytes(8));
if (!mkdir($fixture . '/wp-admin/includes', 0700, true)) {
    throw new RuntimeException('Could not create fixture directory.');
}
file_put_contents($fixture . '/wp-admin/includes/user.php', "<?php\n");
define('ABSPATH', $fixture . '/');

class WP_Error {
    public function get_error_message() { return 'Simulated failure'; }
}
class WP_User {
    private $id;
    private $site_id;
    public function __construct($id, $name = '', $site_id = 0) {
        $this->id = $id;
        $this->site_id = $site_id ?: get_current_blog_id();
    }
    public function set_role($role) { $GLOBALS['sample_test_roles'][$this->site_id][$this->id] = array($role); }
    public function add_role($role) {
        if ($this->site_id !== get_current_blog_id()) {
            throw new RuntimeException('Additional roles must be assigned in their site context.');
        }
        $GLOBALS['sample_test_roles'][$this->site_id][$this->id][] = $role;
    }
}
function is_wp_error($value) { return $value instanceof WP_Error; }
function is_multisite() { return $GLOBALS['sample_test_context'] !== 'single'; }
function is_main_site() { return $GLOBALS['sample_test_context'] !== 'subsite'; }
function get_current_blog_id() { return $GLOBALS['sample_test_current_site'] ?? 7; }
function get_main_site_id() { return 3; }
function switch_to_blog($site_id) {
    $GLOBALS['sample_test_site_stack'][] = get_current_blog_id();
    $GLOBALS['sample_test_current_site'] = $site_id;
}
function restore_current_blog() {
    $GLOBALS['sample_test_current_site'] = array_pop($GLOBALS['sample_test_site_stack']);
}
function username_exists($login) { return $login === 'gabby-giver' ? 100 : false; }
function wp_insert_user($user) {
    if ($user['user_login'] === $GLOBALS['sample_test_insert_failure']) {
        return new WP_Error();
    }
    $id = count($GLOBALS['sample_test_created']) + 1;
    $GLOBALS['sample_test_created'][$id] = $user;
    return $id;
}
function add_user_to_blog($blog_id, $user_id, $role) {
    $GLOBALS['sample_test_additions'][] = array($blog_id, $user_id, $role);
    if ($GLOBALS['sample_test_created'][$user_id]['user_login'] === $GLOBALS['sample_test_add_failure']
        && $blog_id === $GLOBALS['sample_test_failure_site']) {
        return new WP_Error();
    }
    $GLOBALS['sample_test_roles'][$blog_id][$user_id] = array($role);
    return true;
}
function loopis_elog_function_start($name) {}
function loopis_elog_first_level($message) { $GLOBALS['sample_test_logs'][] = $message; }
function loopis_elog_function_end_success($name) { $GLOBALS['sample_test_log_result'] = 'success'; }
function loopis_elog_function_end_failure($name) { $GLOBALS['sample_test_log_result'] = 'failure'; }
function sample_test_assert($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

try {
    require dirname(__DIR__, 2) . '/includes/samples/sample_users_insert.php';

    foreach (array('single', 'mainsite', 'subsite') as $context) {
        $GLOBALS['sample_test_context'] = $context;
        $GLOBALS['sample_test_created'] = array();
        $GLOBALS['sample_test_roles'] = array();
        $GLOBALS['sample_test_additions'] = array();
        $GLOBALS['sample_test_logs'] = array();
        $GLOBALS['sample_test_insert_failure'] = '';
        $GLOBALS['sample_test_add_failure'] = '';
        $GLOBALS['sample_test_failure_site'] = 0;
        $GLOBALS['sample_test_current_site'] = 7;
        $GLOBALS['sample_test_site_stack'] = array();
        $result = develooper_users_insert();

        sample_test_assert($result['existed'] === array('gabby-giver') && count($result['inserted']) === 4 && $result['failed'] === array(), 'Existing users must be skipped and other users created.');
        foreach ($GLOBALS['sample_test_created'] as $id => $user) {
            sample_test_assert($user['display_name'] === $user['first_name'] . ' ' . $user['last_name'], 'Display name must contain the first and last names.');
            $expected_roles = array(
                'fred-fetcher' => array('member'),
                'rebecca-raffle' => array('member'),
                'jessica-joiner' => array('member_pending'),
                'monica-manager' => array('member', 'manager'),
            );
            foreach ($context === 'subsite' ? array(7, 3) : array(7) as $site_id) {
                sample_test_assert($GLOBALS['sample_test_roles'][$site_id][$id] === $expected_roles[$user['user_login']], 'Incorrect roles for ' . $user['user_login'] . ' on site ' . $site_id . ' in ' . $context . '.');
            }
        }
        if ($context === 'subsite') {
            sample_test_assert(count($GLOBALS['sample_test_additions']) === 8, 'Every new subsite user must be added to both sites.');
            foreach ($GLOBALS['sample_test_additions'] as $addition) {
                $login = $GLOBALS['sample_test_created'][$addition[1]]['user_login'];
                sample_test_assert(in_array($addition[0], array(7, 3), true) && $addition[2] === $expected_roles[$login][0], 'Wrong site or initial role for ' . $login . '.');
            }
        } else {
            sample_test_assert($GLOBALS['sample_test_additions'] === array(), 'Main and single sites must retain configured roles.');
        }
        sample_test_assert($GLOBALS['sample_test_log_result'] === 'success', 'Successful insertion must be logged.');
        sample_test_assert(get_current_blog_id() === 7 && $GLOBALS['sample_test_site_stack'] === array(), 'The current site must be restored after assigning roles.');
    }

    foreach (array(7, 3) as $failure_site) {
        $GLOBALS['sample_test_context'] = 'subsite';
        $GLOBALS['sample_test_created'] = array();
        $GLOBALS['sample_test_roles'] = array();
        $GLOBALS['sample_test_additions'] = array();
        $GLOBALS['sample_test_logs'] = array();
        $GLOBALS['sample_test_insert_failure'] = 'fred-fetcher';
        $GLOBALS['sample_test_add_failure'] = 'monica-manager';
        $GLOBALS['sample_test_failure_site'] = $failure_site;
        $GLOBALS['sample_test_current_site'] = 7;
        $GLOBALS['sample_test_site_stack'] = array();
        $result = develooper_users_insert();
        $monica_id = null;
        foreach ($GLOBALS['sample_test_created'] as $id => $created_user) {
            if ($created_user['user_login'] === 'monica-manager') {
                $monica_id = $id;
                break;
            }
        }
        $other_site = $failure_site === 7 ? 3 : 7;
        sample_test_assert($monica_id !== null && $result['failed'] === array('fred-fetcher', 'monica-manager')
            && count($result['inserted']) === 2 && $GLOBALS['sample_test_log_result'] === 'failure'
            && strpos(implode("\n", $GLOBALS['sample_test_logs']), 'could not be added to site ' . $failure_site) !== false
            && $GLOBALS['sample_test_roles'][$other_site][$monica_id] === array('member', 'manager')
            && get_current_blog_id() === 7 && $GLOBALS['sample_test_site_stack'] === array(),
            'Creation and site-add failures must be logged and reported, while the other site is still processed.');
    }

    echo "Sample user tests passed.\n";
} finally {
    unlink($fixture . '/wp-admin/includes/user.php');
    rmdir($fixture . '/wp-admin/includes');
    rmdir($fixture . '/wp-admin');
    rmdir($fixture);
}
