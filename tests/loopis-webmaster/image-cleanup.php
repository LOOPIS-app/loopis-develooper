<?php
/** Standalone image cleanup regression tests. */
require __DIR__ . '/image-test-helpers.php';
require dirname(__DIR__, 3) . '/loopis-webmaster/includes/image-cleanup/page.php';

function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function esc_url($url) { return htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function esc_attr($value) { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

class Cleanup_Test_DB extends Migration_Test_DB {
    public $args = array();
    public $content = array();
    public $registrations = array();
    public $lookup_error = false;
    public $usage_checks = 0;
    public $add_reference_on_usage_check = 0;

    public function esc_like($value) { return addcslashes($value, '_%\\'); }
    public function prepare($sql, ...$args) {
        $this->args = count($args) === 1 && is_array($args[0]) ? $args[0] : $args;
        return $sql;
    }
    public function get_var($sql) {
        if (strpos($sql, 'SELECT 1 FROM (') !== false) {
            $this->usage_checks++;
            if ($this->usage_checks === $this->add_reference_on_usage_check) return '1';
            $this->last_error = $this->lookup_error ? 'Simulated lookup error' : '';
            if ($this->last_error) return null;
            foreach ($this->content as $value) {
                foreach ($this->args as $arg) {
                    if (strpos($arg, '(^|[^0-9])') === 0) {
                        if (preg_match('/' . $arg . '/', $value)) return '1';
                    } elseif (stripos($value, stripcslashes(trim($arg, '%'))) !== false) {
                        return '1';
                    }
                }
            }
            return null;
        }
        if (strpos($sql, "p.post_type = 'attachment'") !== false) {
            $this->last_error = $this->lookup_error ? 'Simulated lookup error' : '';
            if ($this->last_error) return null;
            foreach ($this->registrations as $id => $value) {
                if ($id !== $this->args[0] && stripos($value, stripcslashes(trim($this->args[1], '%'))) !== false) return (string) $id;
            }
            return null;
        }
        $this->last_error = '';
        return parent::get_var($sql);
    }
    public function insert($table, $data, $format) {
        if ($table !== $this->prefix . 'loopis_image_cleanup') throw new RuntimeException('Unexpected table: ' . $table);
        $data['id'] = ++$this->insert_id;
        $this->items[$data['id']] = $data;
        return 1;
    }
    public function query($sql) {
        if (strpos($sql, "SET status = 'pending' WHERE status = 'review'") !== false) {
            $count = 0;
            foreach ($this->items as &$item) {
                if ($item['status'] === 'review') { $item['status'] = 'pending'; $count++; }
            }
            return $count;
        }
        if (strpos($sql, "SET status = 'superseded'") !== false) {
            foreach ($this->items as &$item) {
                if (in_array($item['status'], array('review', 'pending', 'skipped'), true)) $item['status'] = 'superseded';
            }
            return count($this->items);
        }
        if (strpos($sql, "SET status = 'failed'") !== false) {
            foreach ($this->items as &$item) {
                if ($item['status'] === 'review') $item['status'] = 'failed';
            }
            return 1;
        }
        throw new RuntimeException('Unexpected cleanup query: ' . $sql);
    }
}

class Reset_Test_DB extends Migration_Test_DB {
    public $queues = array('app_loopis_image_conversion' => array(), 'app_loopis_image_cleanup' => array());
    public $fail_write = false;
    public function get_var($sql) {
        if (preg_match("/FROM (app_loopis_image_(?:conversion|cleanup)) WHERE status = 'processing'/", $sql, $match)) {
            return count(array_filter($this->queues[$match[1]], static function($status) { return $status === 'processing'; }));
        }
        return parent::get_var($sql);
    }
    public function query($sql) {
        if (preg_match('/^(DELETE FROM|DROP TABLE) (app_loopis_image_(?:conversion|cleanup))$/', $sql, $match)) {
            if ($this->fail_write) {
                $this->last_error = 'Simulated queue failure';
                return false;
            }
            $this->queues[$match[2]] = array();
            return 1;
        }
        throw new RuntimeException('Unexpected reset query: ' . $sql);
    }
}

try {
    $base = $fixture . '/cleanup';
    mkdir($base);
    mkdir($base . '/2026/11', 0777, true);
    $review_path = $base . '/2026/11/p42992-img-1 & test.jpg';
    file_put_contents($review_path, 'preview');
    $upload = array('basedir' => $base, 'baseurl' => 'https://example.test/wp-content/uploads/sites/2');
    $review_file = loopis_image_cleanup_review_file($review_path, $upload);
    migration_test_assert($review_file['display'] === 'uploads/sites/2/2026/11/p42992-img-1 & test.jpg', 'Review paths must omit the server-specific prefix.');
    migration_test_assert($review_file['url'] === 'https://example.test/wp-content/uploads/sites/2/2026/11/p42992-img-1%20%26%20test.jpg', 'Preview URLs must encode each filename component.');
    ob_start();
    loopis_image_cleanup_render_review_file($review_path, $upload);
    $review_html = ob_get_clean();
    migration_test_assert(strpos($review_html, 'p42992-img-1 &amp; test.jpg') !== false && strpos($review_html, 'data-preview-url=') !== false && strpos($review_html, '<img alt="" hidden>') !== false, 'Review markup must escape filenames and load previews on demand.');
    $missing = loopis_image_cleanup_review_file($base . '/2026/11/missing.jpg', $upload);
    migration_test_assert($missing['display'] === 'uploads/sites/2/2026/11/missing.jpg' && $missing['url'] === '' && $missing['error'] !== '', 'Missing files must retain a short path and report an unavailable preview.');
    file_put_contents($fixture . '/outside.jpg', 'outside');
    symlink($fixture . '/outside.jpg', $base . '/2026/11/link.jpg');
    migration_test_assert(loopis_image_cleanup_review_file($base . '/2026/11/link.jpg', $upload)['url'] === '' && loopis_image_cleanup_review_file($fixture . '/outside.jpg', $upload)['url'] === '', 'Symlinks and files outside the current site must not receive preview URLs.');
    symlink($base, $fixture . '/cleanup-alias');
    $alias_upload = array('basedir' => $fixture . '/cleanup-alias', 'baseurl' => $upload['baseurl']);
    migration_test_assert(loopis_image_cleanup_review_file($fixture . '/cleanup-alias/2026/11/p42992-img-1 & test.jpg', $alias_upload)['url'] === $review_file['url'], 'An uploads root symlink must map to the same preview.');
    unlink($base . '/2026/11/link.jpg');
    unlink($review_path);
    rmdir($base . '/2026/11');
    rmdir($base . '/2026');
    $GLOBALS['migration_test_dir'] = $base;
    $GLOBALS['migration_test_deleted_attachments'] = array();
    $wpdb = new Cleanup_Test_DB();
    $GLOBALS['migration_test_attachments'] = array();
    $GLOBALS['migration_test_files'] = array();
    $GLOBALS['migration_test_metadata'] = array();
    foreach (array(90001 => 'shared.webp', 90002 => 'unused.webp', 90003 => 'content.webp') as $id => $filename) {
        file_put_contents($base . '/' . $filename, $filename);
        $GLOBALS['migration_test_attachments'][] = (object) array('ID' => $id, 'post_parent' => 0, 'post_mime_type' => 'image/webp');
        $GLOBALS['migration_test_posts'][$id] = (object) array('post_type' => 'attachment', 'post_parent' => 0);
        $GLOBALS['migration_test_files'][$id] = $base . '/' . $filename;
        $GLOBALS['migration_test_metadata'][$id] = array('file' => $filename);
        $wpdb->registrations[$id] = $filename;
    }
    file_put_contents($base . '/unused-thumb.webp', 'thumb');
    file_put_contents($base . '/unused-backup.webp', 'backup');
    $GLOBALS['migration_test_metadata'][90002]['sizes'] = array('thumbnail' => array('file' => 'unused-thumb.webp'));
    $GLOBALS['migration_test_backups'][90002] = array('full-orig' => array('file' => 'unused-backup.webp'));
    $wpdb->registrations[90002] .= ' unused-thumb.webp unused-backup.webp';
    $wpdb->content = array('forwarded post _thumbnail_id 90001', 'page <img src="/uploads/content.webp">');
    for ($i = 0; $i < 205; $i++) file_put_contents($base . '/orphan-' . $i . '.jpg', 'image ' . $i);
    $scan = loopis_image_cleanup_scan();
    migration_test_assert($scan === 206 && get_option('loopis_image_cleanup_scan_ready') === '1', 'Cleanup scan must protect shared and content images, but find unused attachments and hundreds of unregistered files.');
    $by_id = array_values(array_filter($wpdb->items, static function($item) { return $item['attachment_id'] === 90002; }));
    migration_test_assert(count($by_id) === 1 && count(json_decode($by_id[0]['payload'], true)) === 3, 'Cleanup must include thumbnail and backup files in its snapshot.');
    migration_test_assert(loopis_image_cleanup_mutate_queue('skip', 2) === 1, 'Review must persist a per-item skip.');
    migration_test_assert(loopis_image_cleanup_mutate_queue('accept') === 205, 'Accept all must queue every remaining candidate, including across pages.');
    migration_test_assert($wpdb->items[2]['status'] === 'skipped' && is_file($base . '/unused.webp'), 'Accept all must not move files or include skipped items.');
    $wpdb->content[] = 'new reference 90002';
    $stale = loopis_image_cleanup_process_next();
    migration_test_assert(is_wp_error($stale) && $stale->get_error_code() === 'now_used' && is_file($base . '/unused.webp'), 'New references must prevent quarantine.');
    $wpdb->content = array('forwarded post _thumbnail_id 90001', 'page <img src="/uploads/content.webp">');
    $wpdb->items[1]['status'] = 'pending';
    wp_mkdir_p($base . '/image-cleanup/quarantine');
    file_put_contents($base . '/image-cleanup/quarantine/cleanup-1-unused-thumb.webp', 'existing');
    $collision = loopis_image_cleanup_process_next();
    migration_test_assert(is_wp_error($collision) && $collision->get_error_code() === 'destination_exists', 'Quarantine must refuse an existing destination.');
    migration_test_assert(is_file($base . '/unused.webp') && is_file($base . '/unused-thumb.webp') && empty($GLOBALS['migration_test_deleted_attachments']), 'A collision must restore moved files and keep the attachment.');
    unlink($base . '/image-cleanup/quarantine/cleanup-1-unused-thumb.webp');
    $wpdb->items[1]['status'] = 'pending';
    $wpdb->add_reference_on_usage_check = $wpdb->usage_checks + 2;
    $during_move = loopis_image_cleanup_process_next();
    migration_test_assert(is_wp_error($during_move) && $during_move->get_error_code() === 'reference_changed' && is_file($base . '/unused.webp') && empty($GLOBALS['migration_test_deleted_attachments']), 'New references during moves must restore files before attachment deletion.');
    $wpdb->add_reference_on_usage_check = 0;
    $wpdb->items[1]['status'] = 'pending';
    $GLOBALS['migration_test_delete_failure'] = true;
    $delete_failure = loopis_image_cleanup_process_next();
    migration_test_assert(is_wp_error($delete_failure) && $delete_failure->get_error_code() === 'delete_failed' && is_file($base . '/unused-backup.webp'), 'Failed attachment deletion must restore all files.');
    $GLOBALS['migration_test_delete_failure'] = false;
    $wpdb->items[1]['status'] = 'pending';
    $success = loopis_image_cleanup_process_next();
    migration_test_assert(!is_wp_error($success) && $GLOBALS['migration_test_deleted_attachments'] === array(90002) && is_file($base . '/image-cleanup/quarantine/cleanup-1-unused-backup.webp'), 'Successful cleanup must quarantine all files before deleting the attachment.');
    $wpdb->lookup_error = true;
    $lookup_failure = loopis_image_cleanup_process_next();
    migration_test_assert(is_wp_error($lookup_failure) && $lookup_failure->get_error_code() === 'usage_lookup_failed', 'A usage lookup failure must stop processing.');
    $wpdb->lookup_error = false;
    $wpdb->last_error = '';
    $wpdb->items[3]['status'] = 'pending';
    file_put_contents($wpdb->items[3]['source_path'], 'changed after review');
    $changed = loopis_image_cleanup_process_next();
    migration_test_assert(is_wp_error($changed) && $changed->get_error_code() === 'files_changed' && is_file($wpdb->items[3]['source_path']), 'Changed files must not be moved.');
    $wpdb->items[4]['status'] = 'pending';
    $wpdb->registrations[90100] = basename($wpdb->items[4]['source_path']);
    $new_registration = loopis_image_cleanup_process_next();
    migration_test_assert(is_wp_error($new_registration) && $new_registration->get_error_code() === 'now_registered' && is_file($wpdb->items[4]['source_path']), 'Newly registered files must not be moved.');
    migration_test_assert(loopis_image_cleanup_is_used(90001, array()) === true, 'A forwarded attachment ID must count as usage.');
    migration_test_assert(loopis_image_cleanup_is_used(0, array($base . '/content.webp')) === true, 'A content image filename must count as usage.');
    migration_test_assert(loopis_image_cleanup_files_registered_elsewhere(0, array($base . '/shared.webp')) === true, 'Registered files must not be treated as unregistered.');
    migration_test_assert(is_file($base . '/image-cleanup/.htaccess') && is_file($base . '/image-cleanup/index.php'), 'Quarantine directory protections must be installed.');
    mkdir($base . '/empty/inner', 0777, true);
    mkdir($base . '/sites/2/empty', 0777, true);
    $directories = loopis_image_cleanup_empty_directories();
    migration_test_assert(!is_wp_error($directories) && !is_dir($base . '/empty') && is_dir($base . '/sites/2/empty'), 'Explicit directory cleanup must remove only empty in-scope directories.');
    $wpdb->lookup_error = true;
    $failed_scan = loopis_image_cleanup_scan();
    migration_test_assert(is_wp_error($failed_scan) && get_option('loopis_image_cleanup_scan_ready') === '0', 'An incomplete scan must disable review and processing.');
    migration_test_assert(is_wp_error(loopis_image_cleanup_mutate_queue('accept')) && is_wp_error(loopis_image_cleanup_process_next()), 'An incomplete scan must not be accepted or processed.');
    $wpdb = new Reset_Test_DB();
    $wpdb->queues['app_loopis_image_conversion'] = array('pending');
    $wpdb->queues['app_loopis_image_cleanup'] = array('processing');
    $conversion_log = loopis_image_conversion_log_file();
    $cleanup_log = loopis_image_conversion_log_file('cleanup');
    migration_test_assert(basename($conversion_log) === 'image-migration-site-1.jsonl' && basename($cleanup_log) === 'image-cleanup-site-1.jsonl', 'Image tools must use separate, tool-first log filenames.');
    file_put_contents($conversion_log, '');
    file_put_contents($cleanup_log, '');
    loopis_image_conversion_log(7, 'completed', 'conversion entry');
    loopis_image_conversion_log(8, 'cleanup_scan', 'cleanup entry', 'cleanup');
    migration_test_assert(count(loopis_image_conversion_recent_activity($conversion_log)) === 1 && loopis_image_conversion_recent_activity($conversion_log)[0]->item_id === 7 && loopis_image_conversion_recent_activity($cleanup_log)[0]->item_id === 8, 'Tools must write activity to separate files.');
    file_put_contents($conversion_log, "conversion history\n");
    file_put_contents($cleanup_log, "cleanup history\n");
    $blocked = loopis_image_tools_reset('conversion', 'clear');
    migration_test_assert(is_wp_error($blocked) && $blocked->get_error_code() === 'processing_incomplete' && file_get_contents($conversion_log) === "conversion history\n", 'Reset must refuse while either tool has a processing item.');
    $wpdb->queues['app_loopis_image_cleanup'] = array('review');
    migration_test_assert(loopis_image_tools_reset('conversion', 'clear') === true, 'Conversion reset must succeed.');
    migration_test_assert($wpdb->queues['app_loopis_image_conversion'] === array() && $wpdb->queues['app_loopis_image_cleanup'] === array('review') && file_get_contents($conversion_log) === '' && file_get_contents($cleanup_log) === "cleanup history\n", 'Conversion reset must not affect cleanup queue or log.');
    update_option('loopis_image_cleanup_scan_ready', '1');
    update_option('loopis_image_cleanup_db_version', '1');
    migration_test_assert(loopis_image_tools_reset('cleanup', 'delete') === true, 'Cleanup delete must succeed.');
    migration_test_assert(!file_exists($cleanup_log) && get_option('loopis_image_cleanup_db_version') === '0' && get_option('loopis_image_cleanup_scan_ready') === '0', 'Deleting cleanup must reset schema and scan state.');
    migration_test_assert(is_file(loopis_image_conversion_log_file('cleanup')), 'A deleted activity log must be recreated on demand.');
    $wpdb->fail_write = true;
    file_put_contents($conversion_log, "keep\n");
    $failed = loopis_image_tools_reset('conversion', 'clear');
    migration_test_assert(is_wp_error($failed) && file_get_contents($conversion_log) === "keep\n" && !$wpdb->locked, 'Queue errors must leave the log intact and release the lock.');
    migration_test_assert(is_wp_error(loopis_image_tools_reset('unknown', 'clear')), 'Unknown tools must be rejected.');
    echo "Image cleanup tests passed.\n";
} finally {
    migration_test_remove_fixture($fixture);
}
