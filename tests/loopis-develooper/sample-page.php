<?php
/** Run from loopis-develooper with: php tests/loopis-develooper/sample-page.php success|failure|post-failure|both-failure */

$mode = $argv[1] ?? '';
if (!in_array($mode, array('success', 'failure', 'post-failure', 'both-failure'), true)) {
    throw new RuntimeException('Expected success, failure, post-failure, or both-failure.');
}

$fixture = sys_get_temp_dir() . '/loopis-sample-page-test-' . bin2hex(random_bytes(8));
if (!mkdir($fixture . '/wp-includes', 0700, true)) {
    throw new RuntimeException('Could not create fixture directory.');
}
file_put_contents($fixture . '/wp-includes/pluggable.php', "<?php\n");
define('ABSPATH', $fixture . '/');

function develooper_users_insert() { return array('failed' => in_array($GLOBALS['mode'], array('failure', 'both-failure'), true) ? array('sample') : array()); }
class WP_Error {}
function is_wp_error($value) { return $value instanceof WP_Error; }
function develooper_sample_posts_insert() {
    $GLOBALS['posts_called'] = true;
    return in_array($GLOBALS['mode'], array('post-failure', 'both-failure'), true) ? new WP_Error() : null;
}
function wp_get_referer() { return 'https://example.test/wp-admin/admin.php?page=develooper_samples'; }
function add_query_arg($key, $value, $url) { return $url . '&' . $key . '=' . $value; }
function wp_redirect($url) { throw new RuntimeException($url); }
function get_sample_users() {
    return array(
        array('user_login' => 'alice', 'first_name' => 'Alice', 'last_name' => 'Example'),
        array('user_login' => 'bob', 'first_name' => '<Bob>', 'last_name' => 'Test & Co'),
        array('user_login' => 'carol', 'first_name' => 'Carol', 'last_name' => 'Missing'),
        array('user_login' => 'dave', 'first_name' => 'Dave', 'last_name' => 'No Role'),
    );
}
function esc_html($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function get_user_by($field, $login) {
    if ($field !== 'login') { throw new RuntimeException('Unexpected user lookup.'); }
    $users = array(
        'alice' => (object) array('ID' => 1, 'roles' => array('member', 'manager<script>')),
        'bob' => (object) array('ID' => 2, 'roles' => array('other-site-role')),
        'dave' => (object) array('ID' => 3, 'roles' => array()),
    );
    return $users[$login] ?? false;
}
function get_current_blog_id() { return 7; }
function is_user_member_of_blog($user_id, $blog_id) {
    if ($blog_id !== 7) { throw new RuntimeException('Incorrect site ID.'); }
    return $user_id === 1 || $user_id === 3;
}
function count_user_posts($user_id, $post_type, $public_only) {
    if ($post_type !== 'post' || $public_only !== true) { throw new RuntimeException('Counts must include only published posts.'); }
    return $user_id === 1 ? '4' : ($user_id === 2 ? '2' : '0');
}

try {
    $_POST['insert_samples'] = 'Insert';
    try {
        require dirname(__DIR__, 2) . '/includes/samples/page.php';
        throw new RuntimeException('Insert handler did not redirect.');
    } catch (RuntimeException $error) {
        $actions = array('success' => 'samples_inserted', 'failure' => 'samples_users_failed', 'post-failure' => 'samples_posts_failed', 'both-failure' => 'samples_users_and_posts_failed');
        $action = $actions[$mode];
        if (strpos($error->getMessage(), '&action=' . $action) === false || empty($GLOBALS['posts_called'])) {
            throw $error;
        }
    }

    $_GET['action'] = $action;
    ob_start();
    develooper_samples_page();
    $page = ob_get_clean();
    if ($mode === 'failure' && (strpos($page, 'notice-error') === false || strpos($page, 'A user may already have been created') === false
        || strpos($page, 'notice-success') !== false)) {
        throw new RuntimeException('Partial user failure must be displayed without a success notice.');
    }
    if ($mode === 'success' && strpos($page, 'notice-success') === false) {
        throw new RuntimeException('Successful insertion must display a success notice.');
    }
    if ($mode === 'post-failure' && (strpos($page, 'notice-error') === false
        || strpos($page, 'Some sample post images could not be reused') === false || strpos($page, 'notice-success') !== false)) {
        throw new RuntimeException('Image reuse failure must display an error notice.');
    }
    if ($mode === 'both-failure' && (strpos($page, 'notice-error') === false
        || strpos($page, 'Some sample users could not be created') === false
        || strpos($page, 'some sample post images could not be reused') === false || strpos($page, 'notice-success') !== false)) {
        throw new RuntimeException('Simultaneous user and image failures must both be reported.');
    }
    if (strpos($page, '<td>Alice Example</td>') === false || strpos($page, '<td>&lt;Bob&gt; Test &amp; Co</td>') === false
        || strpos($page, '<Bob>') !== false || strpos($page, 'name="delete_samples"') > strpos($page, '<h2>Sample users</h2>')) {
        throw new RuntimeException('Sample user names must be escaped and listed below the buttons.');
    }
    if (strpos($page, '<th scope="col" style="width: 100px; max-width: 100px; box-sizing: border-box;">Status</th>') === false) {
        throw new RuntimeException('The status column must be limited to 100 pixels.');
    }
    preg_match_all('/<tr>\s*<td>\s*<span aria-hidden="true">(✅|💢)<\/span>\s*<span class="screen-reader-text">([^<]+)<\/span>\s*<\/td>\s*<td>([^<]+)<\/td>\s*<td>((?:<code>[^<]+<\/code>(?:, <code>[^<]+<\/code>)*|None))<\/td>\s*<td>([^<]+)<\/td>\s*<\/tr>/u', $page, $rows, PREG_SET_ORDER);
    if (count($rows) !== 4 || $rows[0][1] !== '✅' || $rows[0][2] !== 'Exists on this site' || $rows[0][3] !== 'Alice Example'
        || $rows[1][1] !== '💢' || $rows[1][2] !== 'Missing from this site' || $rows[1][3] !== '&lt;Bob&gt; Test &amp; Co'
        || $rows[2][1] !== '💢' || $rows[2][3] !== 'Carol Missing'
        || $rows[3][1] !== '✅' || $rows[3][3] !== 'Dave No Role'
        || $rows[0][4] !== '<code>member</code>, <code>manager&lt;script&gt;</code>' || $rows[1][4] !== 'None'
        || $rows[2][4] !== 'None' || $rows[3][4] !== 'None'
        || $rows[0][5] !== '4' || $rows[1][5] !== '2' || $rows[2][5] !== '0' || $rows[3][5] !== '0'
        || strpos($page, '<th scope="col">Roles</th>') === false
        || strpos($page, '<th scope="col">Posts</th>') === false || substr_count($page, '<table ') !== 1) {
        throw new RuntimeException('The sample user table must show current-site membership, roles and published post counts.');
    }
    echo "Sample page test passed: $mode.\n";
} finally {
    unlink($fixture . '/wp-includes/pluggable.php');
    rmdir($fixture . '/wp-includes');
    rmdir($fixture);
}
