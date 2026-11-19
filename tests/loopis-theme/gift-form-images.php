<?php
/** Run from loopis-develooper with: php tests/loopis-theme/gift-form-images.php */
define('ABSPATH', __DIR__ . '/');
class WP_Error {
    public function __construct($code, $message) {}
}
function is_wp_error($value) { return $value instanceof WP_Error; }
function get_post_thumbnail_id($id) { return $GLOBALS['image_test_posts'][$id]['_thumbnail_id'] ?? 0; }
function get_post_meta($id, $key, $single) { return $GLOBALS['image_test_posts'][$id][$key] ?? ''; }
function set_post_thumbnail($id, $value) { $GLOBALS['image_test_posts'][$id]['_thumbnail_id'] = $value; }
function delete_post_thumbnail($id) { unset($GLOBALS['image_test_posts'][$id]['_thumbnail_id']); }
function update_post_meta($id, $key, $value) {
    if (($GLOBALS['image_test_fail'] ?? '') === $key) {
        $GLOBALS['image_test_fail'] = '';
        return false;
    }
    $GLOBALS['image_test_posts'][$id][$key] = $value;
}
function delete_post_meta($id, $key) { unset($GLOBALS['image_test_posts'][$id][$key]); }
require dirname(__DIR__, 3) . '/loopis-theme/includes/functions/user-extra/gift-form-images.php';

function assert_image_test($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}

$original = array(101, 102, 103);
$files = array(array('name' => 'new.webp'));
$valid = array(
    array('[{"id":101},{"id":102},{"id":103}]', $original, array(), 1),
    array('[{"id":103},{"upload":0}]', $original, $files, 0),
    array('[{"upload":0}]', array(), $files, 0),
);
foreach ($valid as $case) {
    assert_image_test(!is_wp_error(loopis_gift_validate_image_selection(...$case)), 'Valid image selection was rejected.');
}
$invalid = array(
    array('', $original, array(), 0),
    array('[]', $original, array(), 0),
    array('[{"id":999}]', $original, array(), 0),
    array('[{"id":101},{"id":101}]', $original, array(), 0),
    array('[{"upload":0},{"upload":0}]', $original, $files, 0),
    array('[{"id":101}]', $original, $files, 0),
    array('[{"upload":1}]', $original, $files, 0),
    array('[{"id":101}]', $original, array(), 1),
    array('[{"id":101,"upload":0}]', $original, $files, 0),
);
foreach ($invalid as $case) {
    assert_image_test(is_wp_error(loopis_gift_validate_image_selection(...$case)), 'Invalid image selection was accepted.');
}

$GLOBALS['image_test_posts'] = array(
    1 => array('_thumbnail_id' => 101, 'image_2' => 102, 'image_3' => 103),
    2 => array('_thumbnail_id' => 101, 'image_2' => 102, 'image_3' => 103),
);
assert_image_test(loopis_gift_set_image_selection(2, array(101, 102, 103), 1) === true, 'Reordering failed.');
assert_image_test($GLOBALS['image_test_posts'][2] === array('_thumbnail_id' => 102, 'image_2' => 101, 'image_3' => 103), 'Featured and gallery IDs were not reordered.');
assert_image_test(loopis_gift_set_image_selection(2, array(104, 103), 0) === true, 'Rotation replacement failed.');
assert_image_test($GLOBALS['image_test_posts'][2] === array('_thumbnail_id' => 104, 'image_2' => 103), 'Changed image and removal were not saved.');
assert_image_test($GLOBALS['image_test_posts'][1] === array('_thumbnail_id' => 101, 'image_2' => 102, 'image_3' => 103), 'Editing a forwarded post changed the original.');
$before = $GLOBALS['image_test_posts'][2];
$GLOBALS['image_test_fail'] = 'image_2';
assert_image_test(is_wp_error(loopis_gift_set_image_selection(2, array(101, 102, 103), 0)), 'Metadata write failure was not reported.');
assert_image_test($GLOBALS['image_test_posts'][2] === $before, 'Image references were not restored after a failure.');
echo "Gift form image tests passed.\n";
