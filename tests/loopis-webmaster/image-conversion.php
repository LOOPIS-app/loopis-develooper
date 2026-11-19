<?php
/** Standalone image conversion regression tests. */
require __DIR__ . '/image-test-helpers.php';
require dirname(__DIR__, 3) . '/loopis-webmaster/includes/image-conversion/page.php';
require dirname(__DIR__, 3) . '/loopis-content/functions/loopis_image_uploads.php';

class Migration_Test_Image_Editor {
    public $quality;
    public static function supports_mime_type($mime_type) { return $mime_type === 'image/webp'; }
    public function maybe_exif_rotate() { return true; }
    public function get_size() { return array('width' => 100, 'height' => 100); }
    public function set_quality($quality) {
        $this->quality = $quality;
        return new WP_Error('test_stop', 'Stop after quality selection.');
    }
}

class Queue_Upgrade_Test_DB extends Migration_Test_DB {
    public $tables = array();
    public $fail_lookup = false;
    public $fail_rename = false;
    public $requested_table = '';
    public function esc_like($value) { return $value; }
    public function prepare($sql, ...$args) {
        $this->requested_table = $args[0];
        return $sql;
    }
    public function get_var($sql) {
        if (strpos($sql, 'SHOW TABLES LIKE') !== false) {
            $this->last_error = $this->fail_lookup ? 'Simulated lookup failure' : '';
            return !$this->fail_lookup && isset($this->tables[$this->requested_table]) ? $this->requested_table : null;
        }
        return parent::get_var($sql);
    }
    public function query($sql) {
        if ($this->fail_rename) {
            $this->last_error = 'Simulated rename failure';
            return false;
        }
        if ($sql !== 'RENAME TABLE `app_loopis_image_migration` TO `app_loopis_image_conversion`') {
            throw new RuntimeException('Unexpected queue upgrade query: ' . $sql);
        }
        $this->tables['app_loopis_image_conversion'] = $this->tables['app_loopis_image_migration'];
        unset($this->tables['app_loopis_image_migration']);
        return 1;
    }
}

try {
    migration_test_assert(loopis_media_image_quality(82, 'image/webp') === 75, 'WebP thumbnail quality must be 75.');
    migration_test_assert(loopis_media_image_quality(82, 'image/jpeg') === 70 && loopis_media_image_quality(82, 'image/png') === 70, 'JPEG and PNG quality must remain unchanged.');
    migration_test_assert(loopis_media_image_quality(82, 'image/gif') === 82, 'Other formats must keep their original quality.');
    $upload_source = $fixture . '/upload.jpg';
    file_put_contents($upload_source, 'upload');
    $GLOBALS['migration_test_editor'] = new Migration_Test_Image_Editor();
    $upload_result = loopis_process_media_upload(array('file' => $upload_source, 'type' => 'image/jpeg'));
    migration_test_assert(isset($upload_result['error']) && $GLOBALS['migration_test_editor']->quality === 75, 'Full-size uploads must select WebP quality 75.');
    $base = $GLOBALS['migration_test_dir'];
    foreach (array('2024/01', '2024/02', '2026/sites', 'sites/2/2024/01', 'sites/2/empty', 'wp-statistics/cache', 'wp-statistics/empty', 'migration/quarantine/1', '.loopis-image-conversion-staging/1', 'empty/inner', 'outside') as $directory) {
        mkdir($base . '/' . $directory, 0777, true);
    }
    foreach (array('2024/01/own.jpg', '2026/sites/own.png', 'sites/2/2024/01/subsite.jpg', 'wp-statistics/cache/chart.png', 'migration/quarantine/1/new.jpg', '.loopis-image-conversion-staging/1/old.jpg', 'outside/.keep') as $file) {
        file_put_contents($base . '/' . $file, 'fixture');
    }
    mkdir($fixture . '/external');
    file_put_contents($fixture . '/external/image.jpg', 'outside');
    migration_test_assert(symlink($fixture . '/external', $base . '/2025'), 'Could not create year symlink fixture.');
    file_put_contents($base . '/2027', 'not a directory');
    migration_test_assert(loopis_image_tools_year_directories() === array('2026', '2024'), 'Only real, in-scope year directories should be listed.');
    migration_test_assert(loopis_image_tools_year_directory('2024') === realpath($base . '/2024'), 'A selected year should resolve inside uploads.');
    migration_test_assert(is_wp_error(loopis_image_tools_year_directory('../2024')) && is_wp_error(loopis_image_tools_year_directory('2025')) && is_wp_error(loopis_image_tools_year_directory('2027')), 'Traversal, symlink and non-directory year selections must be rejected.');
    $GLOBALS['migration_test_dir'] = $base . '/sites/2';
    migration_test_assert(loopis_image_tools_year_directories() === array('2024'), 'Year discovery must use the current subsite uploads root.');
    migration_test_assert(loopis_image_tools_year_directory('2024') === realpath($base . '/sites/2/2024'), 'Year selection must resolve within the current subsite.');
    migration_test_assert(is_wp_error(loopis_image_tools_year_directory('2026')), 'A year belonging only to another site must not be selectable.');
    $GLOBALS['migration_test_dir'] = $base . '/missing-uploads';
    migration_test_assert(is_wp_error(loopis_image_tools_year_directories()), 'An unavailable uploads root must produce an error.');
    $GLOBALS['migration_test_dir'] = $base;
    mkdir($base . '/2028/01', 0777, true);
    for ($i = 0; $i < 205; $i++) {
        file_put_contents($base . '/2028/01/' . sprintf('%03d.jpg', $i), 'image');
    }
    migration_test_assert(symlink($fixture . '/external/image.jpg', $base . '/2028/01/linked.jpg'), 'Could not create file symlink fixture.');
    migration_test_assert(symlink($fixture . '/external', $base . '/2028/02'), 'Could not create month symlink fixture.');
    $first = loopis_image_tools_month_files('2028', '01');
    $second = loopis_image_tools_month_files('2028', '01', $first['next']);
    $last = loopis_image_tools_month_files('2028', '01', $second['next']);
    migration_test_assert(count($first['files']) === 100 && !$first['complete'] && count($second['files']) === 100 && !$second['complete'] && count($last['files']) === 5 && $last['complete'], 'Each month batch must contain at most 100 files and resume at its cursor.');
    migration_test_assert(count(array_unique(array_merge($first['files'], $second['files'], $last['files']))) === 205, 'Batches must not duplicate files or include symlinks.');
    migration_test_assert(loopis_image_tools_month_files('2028', '01', $first['next']) === $second, 'Replaying a cursor must return the same page when files have not changed.');
    migration_test_assert(is_wp_error(loopis_image_tools_month_files('2028', '02')) && is_wp_error(loopis_image_tools_month_files('2028', '../01')) && is_wp_error(loopis_image_tools_month_files('2028', '01', '../file')), 'Symlink months and unsafe month or cursor values must be rejected.');
    migration_test_assert(loopis_image_tools_month_files('2028', '03')['complete'], 'A missing month must be skipped.');
    mkdir($base . '/2028/01/unexpected');
    migration_test_assert(is_wp_error(loopis_image_tools_month_files('2028', '01', $second['next'])), 'Unexpected month subdirectories must be reported rather than silently ignored.');
    rmdir($base . '/2028/01/unexpected');
    migration_test_assert(symlink($fixture . '/external', $base . '/linked'), 'Could not create symlink fixture.');
    mkdir($base . '/symlink-only');
    migration_test_assert(symlink($fixture . '/external', $base . '/symlink-only/link'), 'Could not create nested symlink fixture.');

    $GLOBALS['migration_test_posts'] = array(
        10 => (object) array('post_type' => 'post', 'post_title' => 'A Gift'),
        11 => (object) array('post_type' => 'post', 'post_title' => ''),
        20 => (object) array('post_type' => 'event', 'post_title' => 'Summer Fair'),
        21 => (object) array('post_type' => 'event', 'post_title' => 'Summer Fair'),
        22 => (object) array('post_type' => 'event', 'post_title' => ''),
    );
    $naming_dir = $fixture . '/external';
    migration_test_assert(loopis_image_conversion_format_path('/uploads/<folder>/a&b.webp') === '/uploads/&lt;folder&gt;/<strong>a&amp;b.webp</strong>', 'Path preview must escape its directory and emphasize its filename.');
    migration_test_assert(loopis_image_conversion_format_path('') === '', 'Empty paths must remain empty.');
    $reserved = array();
    migration_test_assert(loopis_image_conversion_target_path(101, 10, $naming_dir . '/gift.jpg', $reserved) === $naming_dir . '/p10-1.webp', 'Gift featured image filename is incorrect.');
    migration_test_assert(loopis_image_conversion_target_path(102, 10, $naming_dir . '/gift2.jpg', $reserved) === $naming_dir . '/p10-2.webp', 'Gift second slot filename is incorrect.');
    migration_test_assert(loopis_image_conversion_target_path(103, 10, $naming_dir . '/gift3.jpg', $reserved) === $naming_dir . '/p10-3.webp', 'Gift third slot filename is incorrect.');
    migration_test_assert(loopis_image_conversion_target_path(104, 10, $naming_dir . '/other.jpg', $reserved) === $naming_dir . '/a-gift-104.webp', 'Other regular post filename is incorrect.');
    migration_test_assert(loopis_image_conversion_target_path(105, 11, $naming_dir . '/untitled.jpg', $reserved) === $naming_dir . '/post-11-105.webp', 'Untitled regular post fallback is incorrect.');
    migration_test_assert(loopis_image_conversion_target_path(104, 10, $naming_dir . '/other-again.jpg', $reserved) === $naming_dir . '/a-gift-104-1.webp', 'Reserved regular post attachment filenames must not collide.');
    migration_test_assert(loopis_image_conversion_target_path(101, 10, $naming_dir . '/other-gift.jpg', $reserved) === $naming_dir . '/p10-1-1.webp', 'Reserved gift filenames must not collide.');
    migration_test_assert(loopis_image_conversion_target_path(201, 20, $naming_dir . '/event.jpg', $reserved) === $naming_dir . '/summer-fair.webp', 'Custom post featured image should use its title.');
    migration_test_assert(loopis_image_conversion_target_path(202, 20, $naming_dir . '/event2.jpg', $reserved) === $naming_dir . '/summer-fair-2.webp', 'Planned target collision should start at -2.');
    file_put_contents($naming_dir . '/summer-fair-3.webp', 'existing');
    migration_test_assert(loopis_image_conversion_target_path(203, 21, $naming_dir . '/event3.jpg', $reserved) === $naming_dir . '/summer-fair-4.webp', 'Existing and planned targets must both be avoided.');
    migration_test_assert(loopis_image_conversion_target_path(204, 22, $naming_dir . '/empty.jpg', $reserved) === $naming_dir . '/post-22.webp', 'Empty title fallback is incorrect.');
    $GLOBALS['migration_test_sanitized_title'] = '%e4%b8%ad';
    $unicode_reserved = array();
    migration_test_assert(loopis_image_conversion_target_path(206, 20, $naming_dir . '/unicode.jpg', $unicode_reserved) === $naming_dir . '/e4b8ad.webp', 'Encoded title slugs must be normalized before comparing WordPress filenames.');
    unset($GLOBALS['migration_test_sanitized_title']);
    $GLOBALS['migration_test_always_change_filename'] = true;
    $blocked_reserved = array();
    $blocked_target = loopis_image_conversion_target_path(207, 20, $naming_dir . '/blocked.jpg', $blocked_reserved);
    unset($GLOBALS['migration_test_always_change_filename']);
    migration_test_assert(is_wp_error($blocked_target) && $blocked_target->get_error_code() === 'target_name_unavailable', 'Filename filters that always change names must stop rather than loop indefinitely.');
    $same_file_reserved = array();
    file_put_contents($naming_dir . '/summer-fair.webp', 'existing');
    migration_test_assert(loopis_image_conversion_target_path(201, 20, $naming_dir . '/summer-fair.webp', $same_file_reserved) === $naming_dir . '/summer-fair.webp', 'Already correctly named WebP should retain its filename.');
    $existing_file_reserved = array();
    migration_test_assert(loopis_image_conversion_target_path(205, 20, $naming_dir . '/new.jpg', $existing_file_reserved) === $naming_dir . '/summer-fair-2.webp', 'An existing title filename should start numbering at -2.');

    loopis_image_conversion_scan();
    $scan_logs = array_values(array_filter(loopis_image_conversion_recent_activity(loopis_image_conversion_log_file()), static function($entry) { return $entry->status === 'scanned'; }));
    migration_test_assert(count($scan_logs) === 1 && count(explode("\n", $scan_logs[0]->message)) === 4, 'Conversion scan summary must report its counts.');
    migration_test_assert(strpos($scan_logs[0]->message, "0 post-attached images found.\n0 already-matching WebP images skipped.") !== false, 'Scan summary must report found post-attached images followed by matching WebP skips.');
    $pending = array_values(array_filter($wpdb->items, static function($item) { return $item['status'] === 'pending'; }));
    migration_test_assert(count($pending) === 0, 'Conversion scan must not queue unregistered files or directory cleanup.');
    migration_test_assert(!loopis_image_conversion_path_is_in_scope($base . '/sites/2/2024/01/subsite.jpg', $base), 'Subsite file must not be in scope.');

    loopis_image_conversion_scan();
    $pending = array_values(array_filter($wpdb->items, static function($item) { return $item['status'] === 'pending'; }));
    migration_test_assert(count($pending) === 0, 'Rescanning must not add cleanup items.');
    loopis_image_conversion_insert_item('orphan', 0, $base . '/2024/01/own.jpg', '', array());
    loopis_image_conversion_insert_item('convert', 999, $base . '/missing.jpg', '', array());
    update_option('loopis_image_conversion_scan_ready', '0');
    $incomplete = loopis_image_conversion_process_next();
    migration_test_assert(is_wp_error($incomplete) && $incomplete->get_error_code() === 'scan_not_ready' && $wpdb->items[2]['status'] === 'pending', 'An incomplete conversion scan must not process queued items.');
    update_option('loopis_image_conversion_scan_ready', '1');

    $wpdb->locked = true;
    $busy = loopis_image_conversion_process_next();
    migration_test_assert(is_wp_error($busy) && $busy->get_error_code() === 'conversion_busy', 'Concurrent processing must be blocked.');
    $wpdb->locked = false;
    $wpdb->items[1]['status'] = 'processing';
    $blocked = loopis_image_conversion_process_next();
    migration_test_assert(is_wp_error($blocked) && $blocked->get_error_code() === 'processing_incomplete', 'Cleanup must not run while an item is still processing.');
    migration_test_assert(is_dir($base . '/empty/inner') && !$wpdb->locked, 'Blocked cleanup must not remove folders or retain the lock.');
    $wpdb->items[1]['status'] = 'pending';
    $wpdb->items[2]['status'] = 'superseded';
    $result = loopis_image_conversion_process_next();
    migration_test_assert(!is_wp_error($result) && $result['pending'] === 0 && is_file($base . '/2024/01/own.jpg'), 'Conversion must not process a legacy orphan.');
    migration_test_assert(is_dir($base . '/2024/02') && is_dir($base . '/empty'), 'Conversion must not remove empty directories.');
    migration_test_assert(is_dir($base . '/2024/01') && is_file($base . '/outside/.keep'), 'Non-empty directories must be kept.');
    migration_test_assert(is_file($base . '/sites/2/2024/01/subsite.jpg') && is_file($base . '/wp-statistics/cache/chart.png'), 'Protected files must be kept.');
    migration_test_assert(is_dir($base . '/sites/2/empty') && is_dir($base . '/wp-statistics/empty'), 'Empty folders in protected directories must be kept.');
    migration_test_assert(is_dir($base . '/migration/quarantine/1') && is_dir($base . '/.loopis-image-conversion-staging/1'), 'Migration work directories must be kept.');
    migration_test_assert(is_link($base . '/linked') && is_file($fixture . '/external/image.jpg'), 'Symlinks and their targets must be kept.');
    migration_test_assert(is_link($base . '/symlink-only/link'), 'Folders containing symlinks must not be deleted.');
    migration_test_assert(is_dir($base) && !$wpdb->locked, 'Uploads root must remain and the lock must be released.');

    $base = $fixture . '/rescan';
    mkdir($base);
    $GLOBALS['migration_test_dir'] = $base;
    $wpdb = new Migration_Test_DB();
    $cases = array(
        301 => array(31, 'Matching', 'matching.webp', 1600, 100, 'image/webp', 'skipped'),
        302 => array(32, 'Oversized', 'oversized.webp', 1601, 100, 'image/webp', 'pending'),
        303 => array(33, 'Wrong name', 'different.webp', 100, 100, 'image/webp', 'pending'),
        304 => array(34, 'Numbered', 'numbered-2.webp', 100, 1600, 'image/webp', 'skipped'),
        305 => array(35, 'Wrong mime', 'wrong-mime.webp', 100, 100, 'image/jpeg', 'pending'),
        306 => array(36, 'Too tall', 'too-tall.webp', 100, 1601, 'image/webp', 'pending'),
    );
    $GLOBALS['migration_test_attachments'] = array();
    $GLOBALS['migration_test_files'] = array();
    foreach ($cases as $id => $case) {
        list($parent, $title, $filename, $width, $height, $mime) = $case;
        $image = imagecreatetruecolor($width, $height);
        imagewebp($image, $base . '/' . $filename);
        imagedestroy($image);
        $GLOBALS['migration_test_posts'][$parent] = (object) array('post_type' => 'event', 'post_title' => $title);
        $GLOBALS['migration_test_attachments'][] = (object) array('ID' => $id, 'post_parent' => $parent, 'post_mime_type' => $mime);
        $GLOBALS['migration_test_files'][$id] = $base . '/' . $filename;
    }
    loopis_image_conversion_scan();
    $items_by_attachment = array();
    foreach ($wpdb->items as $item) {
        if ($item['attachment_id']) {
            $items_by_attachment[$item['attachment_id']] = $item;
        }
    }
    foreach ($cases as $id => $case) {
        migration_test_assert(isset($items_by_attachment[$id]) && $items_by_attachment[$id]['status'] === $case[6], 'Unexpected scan status for attachment ' . $id);
    }
    $recent = loopis_image_conversion_recent_activity(loopis_image_conversion_log_file());
    migration_test_assert(strpos($recent[0]->message, '2 already-matching WebP images skipped.') !== false, 'Matching WebP count was not reported.');
    migration_test_assert(!loopis_image_conversion_webp_is_within_limits($base . '/matching.webp', 'image/webp', 0, 1600), 'Invalid limits must not qualify for skipping.');
    file_put_contents($base . '/invalid.webp', 'not an image');
    migration_test_assert(!loopis_image_conversion_webp_is_within_limits($base . '/invalid.webp', 'image/webp', 1600, 1600), 'Invalid WebP data must not qualify for skipping.');

    $wpdb = new Migration_Test_DB();
    $GLOBALS['migration_test_attachments'] = array();
    $GLOBALS['migration_test_files'] = array();
    foreach (array(101 => 'p10-img-1.webp', 102 => 'p10-2.webp', 103 => 'p10-3-1.webp', 104 => 'a-gift-image-104.webp', 105 => 'a-gift-105.webp') as $id => $filename) {
        $image = imagecreatetruecolor(10, 10);
        imagewebp($image, $base . '/' . $filename);
        imagedestroy($image);
        $GLOBALS['migration_test_attachments'][] = (object) array('ID' => $id, 'post_parent' => 10, 'post_mime_type' => 'image/webp');
        $GLOBALS['migration_test_files'][$id] = $base . '/' . $filename;
    }
    loopis_image_conversion_scan();
    $items_by_attachment = array();
    foreach ($wpdb->items as $item) {
        if ($item['attachment_id']) {
            $items_by_attachment[$item['attachment_id']] = $item;
        }
    }
    migration_test_assert($items_by_attachment[101]['status'] === 'pending' && $items_by_attachment[101]['target_path'] === $base . '/p10-1.webp', 'An old gift filename must be queued for the new name.');
    migration_test_assert($items_by_attachment[102]['status'] === 'skipped' && $items_by_attachment[103]['status'] === 'skipped', 'Correctly named gift images, including collision suffixes, must be skipped on rescan.');
    migration_test_assert($items_by_attachment[104]['status'] === 'pending' && $items_by_attachment[104]['target_path'] === $base . '/a-gift-104.webp', 'Old regular post filenames must be queued for the new name.');
    migration_test_assert($items_by_attachment[105]['status'] === 'skipped', 'A correctly named regular post attachment must be skipped on rescan.');

    $wpdb = new Migration_Test_DB();
    $failed_path = $base . '/odling-240x240.jpg';
    $expected_message = "Test error.\nSource: " . $failed_path;
    migration_test_assert(loopis_image_conversion_error_details($expected_message, $failed_path) === $expected_message, 'Rendering a recorded failure must not duplicate its source.');
    migration_test_assert(loopis_image_conversion_error_details('Older error.', $failed_path) === "Older error.\nSource: " . $failed_path, 'Older failures must display their queue item source.');
    migration_test_assert(loopis_image_conversion_error_details('Site-wide error.', '') === 'Site-wide error.', 'Site-wide errors without a source must remain unchanged.');

    $log_file = $fixture . '/private-log/image-migration-site-1.jsonl';
    migration_test_assert(!loopis_image_conversion_path_is_inside($log_file, $base), 'The log must not be stored in uploads.');
    migration_test_assert((fileperms($log_file) & 0777) === 0600 && (fileperms(dirname($log_file)) & 0777) === 0700, 'New activity logs must use private permissions.');

    $original_log = file_get_contents($log_file);
    file_put_contents($log_file, '');
    migration_test_assert(loopis_image_conversion_recent_activity($log_file) === array(), 'An empty log must have no recent activity.');
    for ($i = 0; $i < 70; $i++) {
        migration_test_assert(loopis_image_conversion_log($i, 'completed', str_repeat('x', 200)) === true, 'Activity must be written to the file.');
    }
    $recent = loopis_image_conversion_recent_activity($log_file);
    migration_test_assert(count($recent) === 50 && $recent[0]->item_id === 69 && $recent[49]->item_id === 20, 'Recent activity must contain the last 50 entries in reverse order across file blocks.');
    file_put_contents($log_file, "invalid json\n", FILE_APPEND);
    $invalid_log = loopis_image_conversion_recent_activity($log_file);
    migration_test_assert(is_wp_error($invalid_log) && $invalid_log->get_error_code() === 'log_invalid_entry', 'Invalid log entries must be reported.');
    file_put_contents($log_file, $original_log);

    loopis_image_conversion_insert_item('convert', 0, $base . '/another.jpg', '', array());
    rename($log_file, $log_file . '.saved');
    symlink($log_file . '.saved', $log_file);
    $unsafe_log = loopis_image_conversion_process_next();
    migration_test_assert(is_wp_error($unsafe_log) && $unsafe_log->get_error_code() === 'unsafe_log_file', 'Processing must stop if the activity log is a symlink.');
    migration_test_assert(end($wpdb->items)['status'] === 'pending', 'An unsafe log file must leave the item pending.');
    unlink($log_file);
    rename($log_file . '.saved', $log_file);

    $conversion_log = loopis_image_conversion_log_file();
    $wpdb = new Queue_Upgrade_Test_DB();
    $wpdb->tables['app_loopis_image_migration'] = array('pending', 'skipped');
    update_option('loopis_image_conversion_scan_ready', '1');
    migration_test_assert(loopis_image_conversion_upgrade_queue() === true && $wpdb->tables['app_loopis_image_conversion'] === array('pending', 'skipped') && get_option('loopis_image_conversion_scan_ready') === '0', 'Queue upgrade must preserve legacy records but disable unverified processing.');
    $wpdb->tables['app_loopis_image_migration'] = array('pending');
    $conflict = loopis_image_conversion_upgrade_queue();
    migration_test_assert(is_wp_error($conflict) && $conflict->get_error_code() === 'queue_conflict' && isset($wpdb->tables['app_loopis_image_migration'], $wpdb->tables['app_loopis_image_conversion']), 'Two existing queues must stop the upgrade without data loss.');
    unset($wpdb->tables['app_loopis_image_conversion']);
    $wpdb->fail_rename = true;
    migration_test_assert(is_wp_error(loopis_image_conversion_upgrade_queue()) && isset($wpdb->tables['app_loopis_image_migration']), 'Failed rename must preserve the previous queue.');
    $wpdb->fail_rename = false;
    $wpdb->fail_lookup = true;
    migration_test_assert(is_wp_error(loopis_image_conversion_upgrade_queue()), 'Queue lookup errors must stop upgrade.');
    echo "Image conversion tests passed.\n";
} finally {
    migration_test_remove_fixture($fixture);
}
